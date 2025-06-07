<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PDO;
use Nexa\Database\Schema;
use Nexa\Database\Blueprint;
use Nexa\Database\Model;
use Nexa\Database\Seeder;
use Nexa\Database\Migration\MigrationManager;

// Example models
class User extends Model
{
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password', 'age'];
    protected $hidden = ['password'];
    protected $casts = [
        'age' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime'
    ];

    // Relationships
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAdults($query)
    {
        return $query->where('age', '>=', 18);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->name . ' (' . $this->email . ')';
    }

    // Mutators
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = password_hash($value, PASSWORD_DEFAULT);
    }

    // Validation rules
    protected $rules = [
        'name' => 'required|min:2',
        'email' => 'required|email|unique:users',
        'age' => 'integer|min:13'
    ];
}

class Post extends Model
{
    protected $table = 'posts';
    protected $fillable = ['title', 'content', 'user_id', 'published_at'];
    protected $casts = [
        'published_at' => 'datetime',
        'is_published' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tags');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}

class Tag extends Model
{
    protected $table = 'tags';
    protected $fillable = ['name', 'slug'];

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_tags');
    }
}

class Profile extends Model
{
    protected $table = 'profiles';
    protected $fillable = ['user_id', 'bio', 'website', 'avatar'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

// Database setup
function setupDatabase()
{
    $connection = new PDO('sqlite:' . __DIR__ . '/blog.db', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $schema = new Schema($connection);

    // Drop tables if they exist
    $schema->dropIfExists('post_tags');
    $schema->dropIfExists('tags');
    $schema->dropIfExists('profiles');
    $schema->dropIfExists('posts');
    $schema->dropIfExists('users');

    // Create users table
    $schema->create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->integer('age')->nullable();
        $table->integer('is_active')->default(1);
        $table->timestamp('email_verified_at')->nullable();
        $table->timestamps();
        $table->timestamp('deleted_at')->nullable()->default(null);
    });

    // Create posts table
    $schema->create('posts', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->text('content');
        $table->foreignId('user_id')->constrained();
        $table->boolean('is_published')->default(false);
        $table->timestamp('published_at')->nullable();
        $table->timestamps();
        $table->timestamp('deleted_at')->nullable()->default(null);
        
        $table->index(['user_id', 'is_published']);
    });

    // Create tags table
    $schema->create('tags', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->timestamps();
    });

    // Create post_tags pivot table
    $schema->create('post_tags', function (Blueprint $table) {
        $table->id();
        $table->foreignId('post_id')->constrained();
        $table->foreignId('tag_id')->constrained();
        $table->timestamps();
        
        $table->unique(['post_id', 'tag_id']);
    });

    // Create profiles table
    $schema->create('profiles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained();
        $table->text('bio')->nullable();
        $table->string('website')->nullable();
        $table->string('avatar')->nullable();
        $table->timestamps();
    });

    return $connection;
}

// Seeder class
class BlogSeeder extends Seeder
{
    public function run()
    {
        // Clear existing data
        $this->delete('post_tags');
        $this->delete('tags');
        $this->delete('profiles');
        $this->delete('posts');
        $this->delete('users');
        
        // Seed users
        $timestamp = time();
        $users = [
            [
                'name' => 'John Doe',
                'email' => 'john' . $timestamp . '@example.com',
                'password' => 'password123',
                'age' => 25,
                'is_active' => true
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane' . ($timestamp + 1) . '@example.com',
                'password' => 'password123',
                'age' => 30,
                'is_active' => true
            ],
            [
                'name' => 'Bob Wilson',
                'email' => 'bob' . ($timestamp + 2) . '@example.com',
                'password' => 'password123',
                'age' => 22,
                'is_active' => false
            ]
        ];

        foreach ($users as $userData) {
            $user = new User();
            $user->fill($userData);
            $user->save();

            // Create profile for each user
            $profile = new Profile();
            $profile->fill([
                'user_id' => $user->getAttribute('id'),
                'bio' => $this->fake()->text(200),
                'website' => 'https://example.com/' . strtolower($user->getAttribute('name'))
            ]);
            $profile->save();
        }

        // Seed tags
        $tags = [
            ['name' => 'PHP', 'slug' => 'php'],
            ['name' => 'JavaScript', 'slug' => 'javascript'],
            ['name' => 'Web Development', 'slug' => 'web-development'],
            ['name' => 'Database', 'slug' => 'database'],
            ['name' => 'Tutorial', 'slug' => 'tutorial']
        ];

        foreach ($tags as $tagData) {
            $tag = new Tag();
            $tag->fill($tagData);
            $tag->save();
        }

        // Seed posts
        $posts = [
            [
                'title' => 'Getting Started with PHP ORM',
                'content' => 'This is a comprehensive guide to using PHP ORM...',
                'user_id' => 1,
                'is_published' => true,
                'published_at' => date('Y-m-d H:i:s')
            ],
            [
                'title' => 'Advanced Database Queries',
                'content' => 'Learn how to write complex database queries...',
                'user_id' => 2,
                'is_published' => true,
                'published_at' => date('Y-m-d H:i:s')
            ],
            [
                'title' => 'Draft Post',
                'content' => 'This is a draft post...',
                'user_id' => 1,
                'is_published' => false
            ]
        ];

        foreach ($posts as $postData) {
            $post = new Post();
            $post->fill($postData);
            $post->save();
        }

        // Attach tags to posts
        $this->table('post_tags')->insert([
            ['post_id' => 1, 'tag_id' => 1], // PHP
            ['post_id' => 1, 'tag_id' => 4], // Database
            ['post_id' => 1, 'tag_id' => 5], // Tutorial
            ['post_id' => 2, 'tag_id' => 4], // Database
            ['post_id' => 2, 'tag_id' => 1], // PHP
        ]);
    }
}

// Example usage
function demonstrateORM()
{
    echo "\n=== Nexa Framework ORM Demo ===\n\n";

    // 1. Basic CRUD operations
    echo "1. Basic CRUD Operations:\n";
    
    // Create
    $user = new User();
    $user->fill([
        'name' => 'Alice Johnson',
        'email' => 'alice' . time() . '@example.com',
        'password' => 'secret123',
        'age' => 28
    ]);
    $user->save();
    echo "Created user: {$user->name} (ID: {$user->id})\n";

    // Read
    $foundUser = User::find($user->id);
    if ($foundUser) {
        echo "Found user: {$foundUser->full_name}\n";
        echo "Found user ID: " . ($foundUser->id ?? 'NULL') . "\n";
    } else {
        echo "Error: User not found with ID: {$user->id}\n";
        return;
    }

    // Update
    $foundUser->age = 29;
    $foundUser->save();
    echo "Updated user age to: {$foundUser->age}\n";

    // 2. Query Builder examples
    echo "\n2. Query Builder Examples:\n";
    
    $activeUsers = User::where('is_active', 1)->get();
    echo "Active users: " . count($activeUsers) . "\n";

    // Complex queries
    $complexUsers = User::where('age', '>', 25)
                       ->where('is_active', 1)
                       ->orderBy('name')
                       ->limit(10)
                       ->get();
    echo "Complex query users: " . count($complexUsers) . "\n";

    $adultUsers = User::where('age', '>=', 18)
                     ->where('is_active', 1)
                     ->orderBy('name')
                     ->get();
    echo "Adult active users: " . count($adultUsers) . "\n";

    // Aggregations
    $avgAge = User::where('is_active', 1)->avg('age');
    echo "Average age of active users: " . round($avgAge, 2) . "\n";

    $maxAge = User::max('age');
    echo "Maximum age: {$maxAge}\n";

    // 3. Advanced queries
    echo "\n3. Advanced Query Examples:\n";
    
    // Date queries
    $recentPosts = Post::whereDate('created_at', '>=', date('Y-m-d', strtotime('-7 days')))
                      ->published()
                      ->recent()
                      ->get();
    echo "Recent published posts: " . count($recentPosts) . "\n";

    // LIKE queries
    $phpPosts = Post::whereLike('title', '%PHP%')->get();
    echo "Posts with 'PHP' in title: " . count($phpPosts) . "\n";

    // BETWEEN queries
    $youngUsers = User::whereBetween('age', [18, 30])->get();
    echo "Users between 18-30 years: " . count($youngUsers) . "\n";

    // 4. Pagination
    echo "\n4. Pagination Examples:\n";
    
    $paginatedUsers = User::where('is_active', 1)->paginate(2, 1);
    echo "Page 1 users (2 per page): " . count($paginatedUsers['data']) . "\n";
    echo "Total users: {$paginatedUsers['total']}\n";
    echo "Last page: {$paginatedUsers['last_page']}\n";

    // 5. Scopes
    echo "\n5. Model Scopes:\n";
    
    $activeAdults = User::active()->adults()->get();
    echo "Active adult users: " . count($activeAdults) . "\n";

    $publishedPosts = Post::published()->recent()->limit(5)->get();
    echo "Recent published posts (limit 5): " . count($publishedPosts) . "\n";

    // 6. Relationships (if implemented)
    echo "\n6. Relationships:\n";
    
    $userWithPosts = User::find(1);
    if ($userWithPosts) {
        echo "User: {$userWithPosts->name}\n";
        // Note: Relationship loading would need to be implemented
        echo "This user's posts would be loaded via relationships\n";
    }

    // 7. Soft Deletes
    echo "\n7. Soft Deletes:\n";
    
    $userToDelete = User::find(3);
    if ($userToDelete) {
        $userToDelete->softDelete();
        echo "Soft deleted user: {$userToDelete->name}\n";
        
        // Query with trashed
        $allUsers = User::withTrashed()->get();
        echo "All users (including soft deleted): " . count($allUsers) . "\n";
        
        $onlyTrashed = User::onlyTrashed()->get();
        echo "Only soft deleted users: " . count($onlyTrashed) . "\n";
    }

    // 8. Mass operations
    echo "\n8. Mass Operations:\n";
    
    // Create multiple
    $timestamp = time();
    $newUsers = [
        ['name' => 'Mike Brown', 'email' => 'mike' . $timestamp . '@example.com', 'age' => 35],
        ['name' => 'Sarah Davis', 'email' => 'sarah' . ($timestamp + 1) . '@example.com', 'age' => 27]
    ];
    
    foreach ($newUsers as $userData) {
        User::create($userData);
    }
    echo "Created multiple users\n";

    // Update multiple
    User::where('age', '<', 25)->updateWhere(['is_active' => 0]);
    echo "Updated users under 25 to inactive\n";

    // 9. Raw queries and debugging
    echo "\n9. Query Debugging:\n";
    
    $query = User::where('age', '>', 20)->orderBy('name');
    echo "SQL Query: " . $query->toSqlWithBindings() . "\n";

    // 10. Chunking for large datasets
    echo "\n10. Chunk Processing:\n";
    
    $processedCount = 0;
    User::where('is_active', 1)->chunk(2, function($users) use (&$processedCount) {
        $processedCount += count($users);
        echo "Processing chunk of " . count($users) . " users\n";
        return true; // Continue processing
    });
    echo "Total processed users: {$processedCount}\n";

    echo "\n=== Demo Complete ===\n";
}

// Run the demo
try {
    // Setup database
    $connection = setupDatabase();
    
    // Set default connection for models
    Model::setDefaultConnection($connection);
    
    // Run seeder
    $seeder = new BlogSeeder($connection);
    $seeder->run();
    echo "Database seeded successfully!\n";
    
    // Demonstrate ORM features
    demonstrateORM();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}