# backend-dev agent

Specialized agent for PHP/Laravel backend development in the Pixely Platform.

## Context
- Framework: Laravel 13+
- Architecture: Core + Extensions pattern
- Database: MySQL / PostgreSQL / SQLite
- Authentication: Laravel Sanctum (SPA mode, session cookies)
- API: `/api/v1/...` with OpenAPI generation via Scramble
- Testing: Pest + PHPUnit, with test factories and feature tests

## Key Conventions

### Controllers
- Orchestrate requests, do not contain business logic
- Validate input, delegate to services/queries
- Return appropriate API responses (JSON)
- Example:
  ```php
  <?php
  
  namespace App\Core\Photos\Http\Controllers;
  
  use App\Core\Photos\Services\PhotoService;
  use Illuminate\Http\Request;
  use Illuminate\Routing\Controller;
  
  class PhotoController extends Controller
  {
      public function __construct(protected PhotoService $service) {}
  
      public function index(Request $request)
      {
          $query = $this->service->getList($request);
          return response()->json($query);
      }

      public function store(Request $request)
      {
          $photo = $this->service->create($request->validated());
          return response()->json($photo, 201);
      }
  }
  ```

### Models
- Use Eloquent, but keep business logic in Services
- Relationships loaded intentionally (avoid N+1)
- Example:
  ```php
  class Photo extends Model
  {
      protected $fillable = ['title', 'url', 'album_id'];
      
      public function album(): BelongsTo
      {
          return $this->belongsTo(Album::class);
      }
  }
  ```

### Services
- Container classes that encapsulate business logic
- Dependency-injected, testable
- Example:
  ```php
  namespace App\Core\Photos\Services;
  
  use App\Core\Photos\Models\Photo;
  use Illuminate\Support\Str;
  
  class PhotoService
  {
      public function create(array $data): Photo
      {
          $data['slug'] = Str::slug($data['title']);
          return Photo::create($data);
      }
  }
  ```

### Database Migrations
- Extensions have their own migrations
- Never modify Core migrations for extension-specific changes
- Use soft deletes where appropriate
- Indexes on frequently queried columns

### API Design
- Versioned: `/api/v1/...`
- Responses use consistent structure
- Pagination when applicable
- OpenAPI docs generated via Scramble
- Error responses: machine-readable code + human message

### Extensions
- Live in `app/Extensions/<Name>/`
- Each has: Http/, Models/, Services/, Database/, Resources/, manifest.json
- Routes: `Route::prefix('v1')->group(...)` under extension's route file
- Protected routes use `auth:sanctum` middleware
- Permissions: `<domain>.<object>.<action>` (e.g., `gallery.photos.manage`)