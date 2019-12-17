<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\User;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private string $password = 'password';

    /** @test */
    public function a_user_can_be_registered()
    {
        $this->withoutExceptionHandling();
        $data = [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->email,
            'password' => $this->password,
            'password_confirmation' => $this->password
        ];

        $response = $this->post('/api/register', $data);

        $response->assertStatus(201)
            ->assertJsonStructure(['user']);

        $this->assertDatabaseHas('users', [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
        ]);
    }

    /** @test */
    public function a_user_can_log_in()
    {
        $this->passportInstall();

        $user = factory(User::class)->create([
            'password' => Hash::make($this->password)
        ]);

        $response = $this->post('/api/login', [
            'email' => $user->email,
            'password' => $this->password
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'token',
                'user'
            ]);

        $this->assertSame(1, DB::table('oauth_access_tokens')->count());
    }

    /** @test */
    public function a_user_can_log_out()
    {
        $this->passportInstall();
        $user = factory(User::class)->create();

        $token = $user->createToken('Personal Access Token')->accessToken;
        $this->assertEquals(0, DB::table('oauth_access_tokens')->first()->revoked);

        $response = $this->post('/api/logout', [], ['Authorization' => 'Bearer ' . $token]);
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['message', 'user']);
        $this->assertEquals(1, DB::table('oauth_access_tokens')->first()->revoked);
    }

    private function passportInstall() {
        $this->artisan('passport:install');
    }
}
