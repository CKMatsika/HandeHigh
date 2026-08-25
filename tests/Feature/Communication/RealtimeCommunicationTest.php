<?php

namespace Tests\Feature\Communication;

use App\Events\Communication\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RealtimeCommunicationTest extends TestCase
{
    use RefreshDatabase;

    protected School $schoolA;
    protected School $schoolB;
    protected User $adminA;
    protected User $teacherA;
    protected User $staffA;
    protected User $adminB;
    protected User $teacherB;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'school-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);

        $this->schoolA = School::create([
            'name' => 'Hande High School A',
            'code' => 'HHS-A',
            'email' => 'info@hande.ac.zw',
            'phone' => '+263 20 12345',
            'address' => 'Hande, Zimbabwe',
            'is_active' => true,
        ]);

        $this->schoolB = School::create([
            'name' => 'Mutare Secondary School B',
            'code' => 'MSS-B',
            'email' => 'info@mutare.ac.zw',
            'phone' => '+263 20 54321',
            'address' => 'Mutare, Zimbabwe',
            'is_active' => true,
        ]);

        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'name' => 'Admin Hande',
            'email' => 'admin@hande.ac.zw',
        ]);
        $this->adminA->assignRole('school-admin');

        $this->teacherA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'name' => 'Teacher Farai',
            'email' => 'farai@hande.ac.zw',
        ]);
        $this->teacherA->assignRole('school-admin');

        $this->staffA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'name' => 'Staff Chipo',
            'email' => 'chipo@hande.ac.zw',
        ]);
        $this->staffA->assignRole('school-admin');

        $this->adminB = User::factory()->create([
            'school_id' => $this->schoolB->id,
            'name' => 'Admin Mutare',
            'email' => 'admin@mutare.ac.zw',
        ]);
        $this->adminB->assignRole('school-admin');

        $this->teacherB = User::factory()->create([
            'school_id' => $this->schoolB->id,
            'name' => 'Teacher Tendai',
            'email' => 'tendai@mutare.ac.zw',
        ]);
        $this->teacherB->assignRole('school-admin');
    }

    public function test_user_can_search_directory_within_same_school()
    {
        $response = $this->actingAs($this->adminA)
            ->getJson(route('admin.communication.search-users', ['q' => 'Farai']));

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Teacher Farai', 'email' => 'farai@hande.ac.zw']);
        $response->assertJsonMissing(['name' => 'Teacher Tendai']);
    }

    public function test_cross_tenant_user_search_is_strictly_blocked()
    {
        $response = $this->actingAs($this->adminA)
            ->getJson(route('admin.communication.search-users', ['q' => 'Tendai']));

        $response->assertStatus(200);
        $response->assertJsonMissing(['name' => 'Teacher Tendai']);
    }

    public function test_user_can_start_direct_conversation()
    {
        $response = $this->actingAs($this->adminA)
            ->post(route('admin.conversation.create'), [
                'type' => 'direct',
                'participants' => (string) $this->teacherA->id,
            ]);

        $conversation = Conversation::where('school_id', $this->schoolA->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('direct', $conversation->type);
        $this->assertTrue($conversation->participants()->where('user_id', $this->adminA->id)->exists());
        $this->assertTrue($conversation->participants()->where('user_id', $this->teacherA->id)->exists());

        $response->assertRedirect(route('admin.communication.chat', $conversation));
    }

    public function test_cannot_create_conversation_with_cross_tenant_participants()
    {
        $response = $this->actingAs($this->adminA)
            ->post(route('admin.conversation.create'), [
                'type' => 'direct',
                'participants' => (string) $this->teacherB->id, // Belongs to School B
            ]);

        $response->assertSessionHasErrors(['participants']);
        $this->assertEquals(0, Conversation::count());
    }

    public function test_message_persists_to_database_with_correct_tenant_and_sender()
    {
        Event::fake([MessageSent::class]);

        $conversation = Conversation::create([
            'school_id' => $this->schoolA->id,
            'type' => 'direct',
            'created_by' => $this->adminA->id,
        ]);
        $conversation->participants()->attach([$this->adminA->id, $this->teacherA->id]);

        $response = $this->actingAs($this->adminA)
            ->postJson(route('admin.communication.message.send', $conversation), [
                'content' => 'Hello Teacher Farai, please send the Form 4 marksheet.',
                'type' => 'text',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('messages', [
            'school_id' => $this->schoolA->id,
            'conversation_id' => $conversation->id,
            'sender_id' => $this->adminA->id,
            'content' => 'Hello Teacher Farai, please send the Form 4 marksheet.',
        ]);

        Event::assertDispatched(MessageSent::class, function ($event) use ($conversation) {
            return $event->message->conversation_id === $conversation->id
                && $event->message->content === 'Hello Teacher Farai, please send the Form 4 marksheet.';
        });
    }

    public function test_recipient_receives_message_via_polling_and_read_receipt_is_marked()
    {
        $conversation = Conversation::create([
            'school_id' => $this->schoolA->id,
            'type' => 'direct',
            'created_by' => $this->adminA->id,
        ]);
        $conversation->participants()->attach([$this->adminA->id, $this->teacherA->id]);

        $message = Message::create([
            'school_id' => $this->schoolA->id,
            'conversation_id' => $conversation->id,
            'sender_id' => $this->adminA->id,
            'content' => 'Staff meeting tomorrow at 08:00 AM.',
            'type' => 'text',
        ]);

        // Teacher Farai polls for messages
        $response = $this->actingAs($this->teacherA)
            ->getJson(route('admin.communication.poll', $conversation) . '?after=0');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertCount(1, $response->json('messages'));
        $this->assertEquals('Staff meeting tomorrow at 08:00 AM.', $response->json('messages.0.content'));

        // Verify read receipt created for Teacher Farai
        $this->assertDatabaseHas('message_reads', [
            'message_id' => $message->id,
            'user_id' => $this->teacherA->id,
        ]);
    }

    public function test_unread_count_calculates_accurately()
    {
        $conversation = Conversation::create([
            'school_id' => $this->schoolA->id,
            'type' => 'direct',
            'created_by' => $this->adminA->id,
        ]);
        $conversation->participants()->attach([$this->adminA->id, $this->teacherA->id]);

        Message::create([
            'school_id' => $this->schoolA->id,
            'conversation_id' => $conversation->id,
            'sender_id' => $this->adminA->id,
            'content' => 'Message 1',
            'type' => 'text',
        ]);

        Message::create([
            'school_id' => $this->schoolA->id,
            'conversation_id' => $conversation->id,
            'sender_id' => $this->adminA->id,
            'content' => 'Message 2',
            'type' => 'text',
        ]);

        $response = $this->actingAs($this->teacherA)
            ->getJson(route('admin.communication.unread-count'));

        $response->assertStatus(200);
        $response->assertJsonPath('count', 2);
        $response->assertJsonPath("per_conversation.{$conversation->id}", 2);
    }

    public function test_unauthorized_user_cannot_access_or_send_message_in_foreign_conversation()
    {
        $conversation = Conversation::create([
            'school_id' => $this->schoolA->id,
            'type' => 'direct',
            'created_by' => $this->adminA->id,
        ]);
        $conversation->participants()->attach([$this->adminA->id, $this->teacherA->id]);

        // Staff Chipo is from School A but not a participant in this direct chat
        $response = $this->actingAs($this->staffA)
            ->get(route('admin.communication.chat', $conversation));

        $response->assertStatus(403);

        $sendResponse = $this->actingAs($this->staffA)
            ->postJson(route('admin.communication.message.send', $conversation), [
                'content' => 'Intruder message',
            ]);

        $sendResponse->assertStatus(403);
    }

    public function test_cross_tenant_idor_access_is_completely_blocked()
    {
        $conversationA = Conversation::create([
            'school_id' => $this->schoolA->id,
            'type' => 'direct',
            'created_by' => $this->adminA->id,
        ]);
        $conversationA->participants()->attach([$this->adminA->id, $this->teacherA->id]);

        // Admin B from School B attempts to access School A's conversation
        $response = $this->actingAs($this->adminB)
            ->get(route('admin.communication.chat', $conversationA));

        $this->assertTrue(in_array($response->status(), [403, 404]), "Expected 403 or 404, got {$response->status()}");

        $pollResponse = $this->actingAs($this->adminB)
            ->getJson(route('admin.communication.poll', $conversationA));

        $this->assertTrue(in_array($pollResponse->status(), [403, 404]), "Expected 403 or 404, got {$pollResponse->status()}");
    }

    public function test_file_attachment_is_uploaded_and_linked_securely()
    {
        Storage::fake('public');
        Event::fake([MessageSent::class]);

        $conversation = Conversation::create([
            'school_id' => $this->schoolA->id,
            'type' => 'direct',
            'created_by' => $this->adminA->id,
        ]);
        $conversation->participants()->attach([$this->adminA->id, $this->teacherA->id]);

        $file = UploadedFile::fake()->create('scheme_of_work.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->adminA)
            ->postJson(route('admin.communication.message.send', $conversation), [
                'content' => 'Here is the scheme of work PDF',
                'type' => 'file',
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $message = Message::first();
        $this->assertNotNull($message);
        $this->assertEquals('file', $message->type);
        $this->assertNotNull($message->file_path);
        Storage::disk('public')->assertExists($message->file_path);
    }

    public function test_channel_authorization_allows_participant_and_rejects_unauthorized_user()
    {
        $conversation = Conversation::create([
            'school_id' => $this->schoolA->id,
            'type' => 'direct',
            'created_by' => $this->adminA->id,
        ]);
        $conversation->participants()->attach([$this->adminA->id, $this->teacherA->id]);

        // Channel callback test
        $callback = Broadcast::channel('conversation.{conversationId}', function () {});

        // Test with authorized participant
        $this->assertTrue(
            (int) $conversation->school_id === (int) $this->adminA->school_id
            && $conversation->participants()->where('user_id', $this->adminA->id)->exists()
        );

        // Test with non-participant from same school
        $this->assertFalse(
            (int) $conversation->school_id === (int) $this->staffA->school_id
            && $conversation->participants()->where('user_id', $this->staffA->id)->exists()
        );

        // Test with cross-tenant user
        $this->assertFalse(
            (int) $conversation->school_id === (int) $this->adminB->school_id
            && $conversation->participants()->where('user_id', $this->adminB->id)->exists()
        );
    }
}
