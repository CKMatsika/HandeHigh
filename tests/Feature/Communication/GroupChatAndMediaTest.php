<?php

namespace Tests\Feature\Communication;

use App\Events\Communication\MessageReactionUpdated;
use App\Events\Communication\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GroupChatAndMediaTest extends TestCase
{
    use RefreshDatabase;

    protected School $schoolA;
    protected School $schoolB;
    protected User $adminA;
    protected User $teacher1A;
    protected User $teacher2A;
    protected User $adminB;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'school-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'bursar', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);

        $this->schoolA = School::create([
            'name' => 'Hande High School A',
            'code' => 'HHS-A',
            'email' => 'admin@hande.ac.zw',
            'is_active' => true,
        ]);

        $this->schoolB = School::create([
            'name' => 'Mutare Secondary School B',
            'code' => 'MSS-B',
            'email' => 'admin@mutare.ac.zw',
            'is_active' => true,
        ]);

        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'name' => 'Admin Hande',
            'email' => 'admin@hande.ac.zw',
        ]);
        $this->adminA->assignRole('school-admin');

        $this->teacher1A = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'name' => 'Bursar Farai',
            'email' => 'farai@hande.ac.zw',
        ]);
        $this->teacher1A->assignRole('bursar');

        $this->teacher2A = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'name' => 'Accountant Chipo',
            'email' => 'chipo@hande.ac.zw',
        ]);
        $this->teacher2A->assignRole('accountant');

        $this->adminB = User::factory()->create([
            'school_id' => $this->schoolB->id,
            'name' => 'Admin Mutare',
            'email' => 'admin@mutare.ac.zw',
        ]);
        $this->adminB->assignRole('school-admin');
    }

    public function test_creates_group_conversation_with_multiple_participants()
    {
        $response = $this->actingAs($this->adminA)
            ->post(route('admin.conversation.create'), [
                'type' => 'group',
                'name' => 'Science Department',
                'participants' => "{$this->teacher1A->id},{$this->teacher2A->id}",
            ]);

        $group = Conversation::where('school_id', $this->schoolA->id)->where('type', 'group')->first();
        $this->assertNotNull($group);
        $this->assertEquals('Science Department', $group->name);
        $this->assertCount(3, $group->participants);
        $response->assertRedirect(route('admin.communication.chat', $group));
    }

    public function test_group_admin_can_add_member_to_group()
    {
        $group = Conversation::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Sports Committee',
            'type' => 'group',
            'created_by' => $this->adminA->id,
        ]);
        $group->participants()->attach($this->adminA->id, ['role' => 'admin', 'joined_at' => now()]);
        $group->participants()->attach($this->teacher1A->id, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->actingAs($this->adminA)
            ->postJson(route('admin.communication.group.add-member', $group), [
                'user_id' => $this->teacher2A->id,
            ]);

        $response->assertStatus(200);
        $this->assertTrue($group->participants()->where('user_id', $this->teacher2A->id)->exists());
    }

    public function test_non_admin_cannot_add_members_to_group()
    {
        $group = Conversation::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Sports Committee',
            'type' => 'group',
            'created_by' => $this->adminA->id,
        ]);
        $group->participants()->attach($this->adminA->id, ['role' => 'admin', 'joined_at' => now()]);
        $group->participants()->attach($this->teacher1A->id, ['role' => 'member', 'joined_at' => now()]);

        // Teacher 1 is regular member
        $response = $this->actingAs($this->teacher1A)
            ->postJson(route('admin.communication.group.add-member', $group), [
                'user_id' => $this->teacher2A->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_user_can_leave_group_conversation()
    {
        $group = Conversation::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Library Club',
            'type' => 'group',
            'created_by' => $this->adminA->id,
        ]);
        $group->participants()->attach($this->adminA->id, ['role' => 'admin', 'joined_at' => now()]);
        $group->participants()->attach($this->teacher1A->id, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->actingAs($this->teacher1A)
            ->deleteJson(route('admin.communication.group.remove-member', [$group, $this->teacher1A]));

        $response->assertStatus(200);
        $this->assertFalse($group->participants()->where('user_id', $this->teacher1A->id)->exists());
    }

    public function test_cross_tenant_group_access_is_denied()
    {
        $group = Conversation::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Hande Staff Only',
            'type' => 'group',
            'created_by' => $this->adminA->id,
        ]);
        $group->participants()->attach($this->adminA->id, ['role' => 'admin', 'joined_at' => now()]);

        $response = $this->actingAs($this->adminB)
            ->get(route('admin.communication.chat', $group));

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    public function test_group_message_broadcasts_realtime_event()
    {
        Event::fake([MessageSent::class]);

        $group = Conversation::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Exam Prep Group',
            'type' => 'group',
            'created_by' => $this->adminA->id,
        ]);
        $group->participants()->attach([$this->adminA->id, $this->teacher1A->id]);

        $response = $this->actingAs($this->adminA)
            ->postJson(route('admin.communication.message.send', $group), [
                'content' => 'Please upload the final Form 4 syllabus items.',
                'type' => 'text',
            ]);

        $response->assertStatus(200);
        Event::assertDispatched(MessageSent::class, function ($event) use ($group) {
            return $event->message->conversation_id === $group->id
                && $event->message->content === 'Please upload the final Form 4 syllabus items.';
        });
    }

    public function test_user_can_toggle_reaction_on_message_and_broadcast_event()
    {
        Event::fake([MessageReactionUpdated::class]);

        $group = Conversation::create([
            'school_id' => $this->schoolA->id,
            'name' => 'General Discussion',
            'type' => 'group',
            'created_by' => $this->adminA->id,
        ]);
        $group->participants()->attach([$this->adminA->id, $this->teacher1A->id]);

        $message = Message::create([
            'school_id' => $this->schoolA->id,
            'conversation_id' => $group->id,
            'sender_id' => $this->adminA->id,
            'content' => 'Great work on the inter-house sports tournament!',
            'type' => 'text',
        ]);

        // Add reaction 👍
        $response = $this->actingAs($this->teacher1A)
            ->postJson(route('admin.communication.message.react', $message), [
                'reaction' => '👍',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('message_reactions', [
            'message_id' => $message->id,
            'user_id' => $this->teacher1A->id,
            'reaction' => '👍',
        ]);

        Event::assertDispatched(MessageReactionUpdated::class);

        // Toggle reaction off
        $toggleOffResponse = $this->actingAs($this->teacher1A)
            ->postJson(route('admin.communication.message.react', $message), [
                'reaction' => '👍',
            ]);

        $toggleOffResponse->assertStatus(200);
        $this->assertDatabaseMissing('message_reactions', [
            'message_id' => $message->id,
            'user_id' => $this->teacher1A->id,
            'reaction' => '👍',
        ]);
    }

    public function test_voice_note_upload_persists_and_broadcasts()
    {
        Storage::fake('public');
        Event::fake([MessageSent::class]);

        $group = Conversation::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Admin Voice Channel',
            'type' => 'group',
            'created_by' => $this->adminA->id,
        ]);
        $group->participants()->attach([$this->adminA->id, $this->teacher1A->id]);

        $audio = UploadedFile::fake()->create('voice.webm', 250, 'audio/webm');

        $response = $this->actingAs($this->adminA)
            ->postJson(route('admin.communication.voice-note', $group), [
                'audio' => $audio,
                'duration' => 45,
            ]);

        $response->assertStatus(200);
        $message = Message::where('conversation_id', $group->id)->where('type', 'voice')->first();
        $this->assertNotNull($message);
        $this->assertEquals('🎤 Voice message (0:45)', $message->content);
        $this->assertNotNull($message->file_path);
        Storage::disk('public')->assertExists($message->file_path);

        Event::assertDispatched(MessageSent::class);
    }
}
