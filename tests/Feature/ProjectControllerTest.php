<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
  /** @test */
public function it_returns_validation_error_when_project_id_is_not_provided_for_show()
{
    // Create a user and authenticate them using Sanctum
    $user = \App\Models\User::factory()->create();

    // Assign the necessary permissions to the user (adjust as per your permissions setup)
    Permission::create([
        'name' => 'view project',
        'guard_name' => 'sanctum',
    ]);
    $user->givePermissionTo('view project'); // Assuming 'view projects' is the permission for the show route

    // Generate a Sanctum token for the user
    $token = $user->createToken('TestToken')->accessToken;

    // Send a POST request without a valid project ID, with the token in the Authorization header
    $response = $this->postJson(route('project.show'), [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    // Assert that the response contains validation errors
    $response->assertStatus(422)
             ->assertJson([
                 'status' => 'failed',
                 'message' => 'The project id field is required.',
             ]);
}


    /** @test */
    public function it_returns_project_when_valid_project_id_is_provided()
    {
        $project = Project::factory()->create();

        // Send a POST request with a valid project ID
        $response = $this->postJson(route('project.show'), ['project_id' => $project->id]);

        // Assert that the response is successful and contains the correct project data
        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Project found',
                     'data' => [
                         'id' => $project->id,
                         'name' => $project->name,
                         // Add other project fields here
                     ]
                 ]);
    }

    /** @test */
    public function it_creates_a_project_successfully()
    {
        $data = [
            'name' => 'New Project',
            // Add other necessary fields for project creation
        ];

        // Send a POST request to create the project
        $response = $this->postJson(route('project.store'), $data);

        // Assert that the response is successful and the project is stored in the database
        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Project created successfully',
                 ]);

        // Assert the project was actually created
        $this->assertDatabaseHas('projects', [
            'name' => 'New Project',
        ]);
    }

    /** @test */
    public function it_returns_error_when_creating_project_with_invalid_data()
    {
        // Send a POST request with invalid data (e.g., missing name)
        $response = $this->postJson(route('project.store'), []);

        // Assert that the validation error is returned
        $response->assertStatus(422)
                 ->assertJson([
                     'status' => 'failed',
                     'message' => 'The name field is required.',
                 ]);
    }

    /** @test */
    public function it_updates_a_project_successfully()
    {
        $project = Project::factory()->create();
        $data = [
            'project_id' => $project->id,
            'name' => 'Updated Project',
            // Add other necessary fields to update the project
        ];

        // Send a POST request to update the project
        $response = $this->postJson(route('project.update'), $data);

        // Assert that the project was updated successfully
        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Project updated successfully',
                 ]);

        // Assert the project was actually updated
        $this->assertDatabaseHas('projects', [
            'name' => 'Updated Project',
        ]);
    }

    /** @test */
    public function it_deletes_a_project_successfully()
    {
        $project = Project::factory()->create();

        $data = [
            'project_id' => $project->id,
        ];

        // Send a POST request to delete the project
        $response = $this->postJson(route('project.destroy'), $data);

        // Assert that the project was deleted successfully
        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Project deleted successfully',
                 ]);

        // Assert that the project was deleted from the database
        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }

    
    /** @test */
    public function it_returns_all_projects()
    {
        // Create multiple projects
        Project::factory()->count(3)->create();

        // Send a POST request to get all projects
        $response = $this->postJson(route('project.show-all'));

        // Assert that the response contains all the projects
        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'All projects retrieved successfully',
                 ])
                 ->assertJsonCount(3, 'data'); // Assuming the projects are returned in the "data" key
    }


    /** @test */
    public function it_returns_project_when_valid_project_id_is_provided_for_show()
    {
        $project = Project::factory()->create();

        // Send a POST request with a valid project ID
        $response = $this->postJson(route('project.show'), ['project_id' => $project->id]);

        // Assert that the response is successful and contains the correct project data
        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Project found',
                     'data' => [
                         'id' => $project->id,
                         'name' => $project->name,
                         // Add other project fields here
                     ]
                 ]);
    }



    /** @test */
    public function it_returns_error_when_updating_project_with_invalid_data()
    {
        $project = Project::factory()->create();

        // Send a POST request with invalid data (e.g., missing project_id)
        $response = $this->postJson(route('project.update'), [
            'name' => 'Updated Project',
        ]);

        // Assert that the validation error is returned
        $response->assertStatus(422)
                 ->assertJson([
                     'status' => 'failed',
                     'message' => 'The project id field is required.',
                 ]);
    }



    /** @test */
    public function it_returns_error_when_deleting_project_with_invalid_data()
    {
        // Send a POST request with invalid data (e.g., missing project_id)
        $response = $this->postJson(route('project.destroy'), []);

        // Assert that the validation error is returned
        $response->assertStatus(422)
                 ->assertJson([
                     'status' => 'failed',
                     'message' => 'The project id field is required.',
                 ]);
    }
}
