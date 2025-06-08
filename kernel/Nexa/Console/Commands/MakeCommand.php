<?php

namespace Nexa\Console\Commands;

use Nexa\Console\Command;
use Nexa\Console\Kernel;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeCommand extends Command
{
    protected static $defaultName = 'make';

    protected function configure()
    {
        $this
            ->setDescription('Generate various Nexa components')
            ->addArgument('type', InputArgument::REQUIRED, 'Type of component (controller, model, middleware, request, resource, seeder, factory, observer, policy, rule, job, event, listener, notification, mail, command)')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the component')
            ->addOption('resource', 'r', InputOption::VALUE_NONE, 'Generate a resource controller')
            ->addOption('api', null, InputOption::VALUE_NONE, 'Generate an API controller')
            ->addOption('migration', 'm', InputOption::VALUE_NONE, 'Create a migration file')
            ->addOption('factory', 'f', InputOption::VALUE_NONE, 'Create a factory')
            ->addOption('seeder', 's', InputOption::VALUE_NONE, 'Create a seeder')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Generate all related files')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite existing files');
    }

    protected function handle()
    {
        $type = $this->input->getArgument('type');
        $name = $this->input->getArgument('name');

        switch ($type) {
            case 'controller':
                return $this->makeController($name);
            case 'model':
                return $this->makeModel($name);
            case 'middleware':
                return $this->makeMiddleware($name);
            case 'request':
                return $this->makeRequest($name);
            case 'resource':
                return $this->makeResource($name);
            case 'seeder':
                return $this->makeSeeder($name);
            case 'factory':
                return $this->makeFactory($name);
            case 'observer':
                return $this->makeObserver($name);
            case 'policy':
                return $this->makePolicy($name);
            case 'rule':
                return $this->makeRule($name);
            case 'job':
                return $this->makeJob($name);
            case 'event':
                return $this->makeEvent($name);
            case 'listener':
                return $this->makeListener($name);
            case 'notification':
                return $this->makeNotification($name);
            case 'mail':
                return $this->makeMail($name);
            case 'command':
                return $this->makeCommand($name);
            default:
                $this->error("Unknown component type: {$type}");
                return 1;
        }
    }

    protected function makeController($name)
    {
        $className = $this->getClassName($name, 'Controller');
        $path = $this->getPath('app/Http/Controllers', $className);

        if ($this->fileExists($path)) {
            return 1;
        }

        $stub = $this->input->getOption('resource') ? $this->getResourceControllerStub() : 
                ($this->input->getOption('api') ? $this->getApiControllerStub() : $this->getControllerStub());
        
        $stub = $this->replaceStub($stub, [
            'DummyNamespace' => 'App\\Http\\Controllers',
            'DummyClass' => $className,
            'DummyModel' => str_replace('Controller', '', $className)
        ]);

        $this->writeFile($path, $stub);
        $this->info("Controller [{$className}] created successfully.");
        return 0;
    }

    protected function makeModel($name)
    {
        $className = $this->getClassName($name);
        $path = $this->getPath('app/Models', $className);

        if ($this->fileExists($path)) {
            return 1;
        }

        $stub = $this->getModelStub();
        $stub = $this->replaceStub($stub, [
            'DummyNamespace' => 'App\\Models',
            'DummyClass' => $className,
            'DummyTable' => $this->getTableName($className)
        ]);

        $this->writeFile($path, $stub);
        $this->info("Model [{$className}] created successfully.");

        // Create migration if requested
        if ($this->input->getOption('migration') || $this->input->getOption('all')) {
            $this->call('make:migration', [
                'name' => 'create_' . $this->getTableName($className) . '_table',
                '--create' => $this->getTableName($className)
            ]);
        }

        // Create factory if requested
        if ($this->input->getOption('factory') || $this->input->getOption('all')) {
            $this->makeFactory($className);
        }

        // Create seeder if requested
        if ($this->input->getOption('seeder') || $this->input->getOption('all')) {
            $this->makeSeeder($className);
        }

        return 0;
    }

    protected function makeMiddleware($name)
    {
        $className = $this->getClassName($name);
        $path = $this->getPath('app/Http/Middleware', $className);

        if ($this->fileExists($path)) {
            return 1;
        }

        $stub = $this->getMiddlewareStub();
        $stub = $this->replaceStub($stub, [
            'DummyNamespace' => 'App\\Http\\Middleware',
            'DummyClass' => $className
        ]);

        $this->writeFile($path, $stub);
        $this->info("Middleware [{$className}] created successfully.");
        return 0;
    }

    protected function makeRequest($name)
    {
        $className = $this->getClassName($name, 'Request');
        $path = $this->getPath('app/Http/Requests', $className);

        if ($this->fileExists($path)) {
            return 1;
        }

        $stub = $this->getRequestStub();
        $stub = $this->replaceStub($stub, [
            'DummyNamespace' => 'App\\Http\\Requests',
            'DummyClass' => $className
        ]);

        $this->writeFile($path, $stub);
        $this->info("Request [{$className}] created successfully.");
        return 0;
    }

    protected function makeResource($name)
    {
        $className = $this->getClassName($name, 'Resource');
        $path = $this->getPath('app/Http/Resources', $className);

        if ($this->fileExists($path)) {
            return 1;
        }

        $stub = $this->getResourceStub();
        $stub = $this->replaceStub($stub, [
            'DummyNamespace' => 'App\\Http\\Resources',
            'DummyClass' => $className
        ]);

        $this->writeFile($path, $stub);
        $this->info("Resource [{$className}] created successfully.");
        return 0;
    }

    protected function makeSeeder($name)
    {
        $className = $this->getClassName($name, 'Seeder');
        $path = $this->getPath('database/seeders', $className);

        if ($this->fileExists($path)) {
            return 1;
        }

        $stub = $this->getSeederStub();
        $stub = $this->replaceStub($stub, [
            'DummyClass' => $className,
            'DummyModel' => str_replace('Seeder', '', $className)
        ]);

        $this->writeFile($path, $stub);
        $this->info("Seeder [{$className}] created successfully.");
        return 0;
    }

    protected function makeFactory($name)
    {
        $className = $this->getClassName($name, 'Factory');
        $path = $this->getPath('database/factories', $className);

        if ($this->fileExists($path)) {
            return 1;
        }

        $stub = $this->getFactoryStub();
        $stub = $this->replaceStub($stub, [
            'DummyClass' => $className,
            'DummyModel' => str_replace('Factory', '', $className)
        ]);

        $this->writeFile($path, $stub);
        $this->info("Factory [{$className}] created successfully.");
        return 0;
    }

    protected function makeObserver($name)
    {
        $className = $this->getClassName($name, 'Observer');
        $path = $this->getPath('app/Observers', $className);

        if ($this->fileExists($path)) {
            return 1;
        }

        $stub = $this->getObserverStub();
        $stub = $this->replaceStub($stub, [
            'DummyNamespace' => 'App\\Observers',
            'DummyClass' => $className,
            'DummyModel' => str_replace('Observer', '', $className)
        ]);

        $this->writeFile($path, $stub);
        $this->info("Observer [{$className}] created successfully.");
        return 0;
    }

    protected function makePolicy($name)
    {
        $className = $this->getClassName($name, 'Policy');
        $path = $this->getPath('app/Policies', $className);

        if ($this->fileExists($path)) {
            return 1;
        }

        $stub = $this->getPolicyStub();
        $stub = $this->replaceStub($stub, [
            'DummyNamespace' => 'App\\Policies',
            'DummyClass' => $className,
            'DummyModel' => str_replace('Policy', '', $className)
        ]);

        $this->writeFile($path, $stub);
        $this->info("Policy [{$className}] created successfully.");
        return 0;
    }

    protected function makeRule($name)
    {
        $className = $this->getClassName($name);
        $path = $this->getPath('app/Rules', $className);

        if ($this->fileExists($path)) {
            return 1;
        }

        $stub = $this->getRuleStub();
        $stub = $this->replaceStub($stub, [
            'DummyNamespace' => 'App\\Rules',
            'DummyClass' => $className
        ]);

        $this->writeFile($path, $stub);
        $this->info("Rule [{$className}] created successfully.");
        return 0;
    }

    protected function makeJob($name)
    {
        $className = $this->getClassName($name);
        $path = $this->getPath('app/Jobs', $className);

        if ($this->fileExists($path)) {
            return 1;
        }

        $stub = $this->getJobStub();
        $stub = $this->replaceStub($stub, [
            'DummyNamespace' => 'App\\Jobs',
            'DummyClass' => $className
        ]);

        $this->writeFile($path, $stub);
        $this->info("Job [{$className}] created successfully.");
        return 0;
    }

    protected function makeEvent($name)
    {
        $className = $this->getClassName($name);
        $path = $this->getPath('app/Events', $className);

        if ($this->fileExists($path)) {
            return 1;
        }

        $stub = $this->getEventStub();
        $stub = $this->replaceStub($stub, [
            'DummyNamespace' => 'App\\Events',
            'DummyClass' => $className
        ]);

        $this->writeFile($path, $stub);
        $this->info("Event [{$className}] created successfully.");
        return 0;
    }

    protected function makeListener($name)
    {
        $className = $this->getClassName($name);
        $path = $this->getPath('app/Listeners', $className);

        if ($this->fileExists($path)) {
            return 1;
        }

        $stub = $this->getListenerStub();
        $stub = $this->replaceStub($stub, [
            'DummyNamespace' => 'App\\Listeners',
            'DummyClass' => $className
        ]);

        $this->writeFile($path, $stub);
        $this->info("Listener [{$className}] created successfully.");
        return 0;
    }

    protected function makeNotification($name)
    {
        $className = $this->getClassName($name);
        $path = $this->getPath('app/Notifications', $className);

        if ($this->fileExists($path)) {
            return 1;
        }

        $stub = $this->getNotificationStub();
        $stub = $this->replaceStub($stub, [
            'DummyNamespace' => 'App\\Notifications',
            'DummyClass' => $className
        ]);

        $this->writeFile($path, $stub);
        $this->info("Notification [{$className}] created successfully.");
        return 0;
    }

    protected function makeMail($name)
    {
        $className = $this->getClassName($name);
        $path = $this->getPath('app/Mail', $className);

        if ($this->fileExists($path)) {
            return 1;
        }

        $stub = $this->getMailStub();
        $stub = $this->replaceStub($stub, [
            'DummyNamespace' => 'App\\Mail',
            'DummyClass' => $className
        ]);

        $this->writeFile($path, $stub);
        $this->info("Mail [{$className}] created successfully.");
        return 0;
    }

    protected function makeCommand($name)
    {
        $className = $this->getClassName($name, 'Command');
        $path = $this->getPath('app/Console/Commands', $className);

        if ($this->fileExists($path)) {
            return 1;
        }

        $stub = $this->getCommandStub();
        $stub = $this->replaceStub($stub, [
            'DummyNamespace' => 'App\\Console\\Commands',
            'DummyClass' => $className,
            'DummyCommand' => strtolower(str_replace('Command', '', $className))
        ]);

        $this->writeFile($path, $stub);
        $this->info("Command [{$className}] created successfully.");
        return 0;
    }

    // Helper methods
    protected function getClassName($name, $suffix = '')
    {
        $name = str_replace(['/', '\\'], '', $name);
        $name = ucfirst($name);
        
        if ($suffix && !str_ends_with($name, $suffix)) {
            $name .= $suffix;
        }
        
        return $name;
    }

    protected function getTableName($className)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className)) . 's';
    }

    protected function getPath($directory, $className)
    {
        $basePath = dirname(__DIR__, 4);
        return $basePath . '/' . $directory . '/' . $className . '.php';
    }

    protected function fileExists($path)
    {
        if (file_exists($path) && !$this->input->getOption('force')) {
            $this->error("File already exists: {$path}");
            return true;
        }
        return false;
    }

    protected function writeFile($path, $content)
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        file_put_contents($path, $content);
    }

    protected function replaceStub($stub, $replacements)
    {
        foreach ($replacements as $search => $replace) {
            $stub = str_replace($search, $replace, $stub);
        }
        return $stub;
    }

    // Stubs
    protected function getControllerStub()
    {
        return <<<'EOT'
<?php

namespace DummyNamespace;

use Nexa\Http\Controller;
use Nexa\Http\Request;
use Nexa\Http\Response;

class DummyClass extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        return $this->json([
            'message' => 'Hello from DummyClass!'
        ]);
    }
}
EOT;
    }

    protected function getResourceControllerStub()
    {
        return <<<'EOT'
<?php

namespace DummyNamespace;

use Nexa\Http\Controller;
use Nexa\Http\Request;
use Nexa\Http\Response;
use App\Models\DummyModel;

class DummyClass extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $items = DummyModel::all();
        return $this->json($items);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): Response
    {
        $item = new DummyModel();
        $item->fill($request->all());
        $item->save();
        
        return $this->json($item, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id): Response
    {
        $item = DummyModel::find($id);
        
        if (!$item) {
            return $this->json(['error' => 'Not found'], 404);
        }
        
        return $this->json($item);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): Response
    {
        $item = DummyModel::find($id);
        
        if (!$item) {
            return $this->json(['error' => 'Not found'], 404);
        }
        
        $item->fill($request->all());
        $item->save();
        
        return $this->json($item);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id): Response
    {
        $item = DummyModel::find($id);
        
        if (!$item) {
            return $this->json(['error' => 'Not found'], 404);
        }
        
        $item->delete();
        
        return $this->json(['message' => 'Deleted successfully']);
    }
}
EOT;
    }

    protected function getApiControllerStub()
    {
        return <<<'EOT'
<?php

namespace DummyNamespace;

use Nexa\Http\Controller;
use Nexa\Http\Request;
use Nexa\Http\Response;

class DummyClass extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        return $this->json([
            'message' => 'API endpoint from DummyClass',
            'data' => []
        ]);
    }
}
EOT;
    }

    protected function getModelStub()
    {
        return <<<'EOT'
<?php

namespace DummyNamespace;

use Nexa\Database\Model;

class DummyClass extends Model
{
    protected $table = 'DummyTable';
    
    protected $fillable = [
        // Add fillable fields here
    ];
    
    protected $hidden = [
        // Add hidden fields here
    ];
    
    protected $casts = [
        // Add field casts here
    ];
    
    // Add relationships and methods here
}
EOT;
    }

    protected function getMiddlewareStub()
    {
        return <<<'EOT'
<?php

namespace DummyNamespace;

use Nexa\Http\Request;
use Nexa\Http\Response;
use Nexa\Middleware\Middleware;

class DummyClass extends Middleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, \Closure $next): Response
    {
        // Add your middleware logic here
        
        return $next($request);
    }
}
EOT;
    }

    protected function getRequestStub()
    {
        return <<<'EOT'
<?php

namespace DummyNamespace;

use Nexa\Http\FormRequest;

class DummyClass extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Add validation rules here
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Add custom messages here
        ];
    }
}
EOT;
    }

    protected function getResourceStub()
    {
        return <<<'EOT'
<?php

namespace DummyNamespace;

use Nexa\Http\JsonResource;

class DummyClass extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            // Add other fields here
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
EOT;
    }

    protected function getSeederStub()
    {
        return <<<'EOT'
<?php

use Nexa\Database\Seeder;
use App\Models\DummyModel;

class DummyClass extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample data
        DummyModel::create([
            // Add sample data here
        ]);
    }
}
EOT;
    }

    protected function getFactoryStub()
    {
        return <<<'EOT'
<?php

use Nexa\Database\Factory;
use App\Models\DummyModel;

class DummyClass extends Factory
{
    protected $model = DummyModel::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            // Add factory definitions here
        ];
    }
}
EOT;
    }

    protected function getObserverStub()
    {
        return <<<'EOT'
<?php

namespace DummyNamespace;

use App\Models\DummyModel;

class DummyClass
{
    /**
     * Handle the DummyModel "created" event.
     */
    public function created(DummyModel $model): void
    {
        //
    }

    /**
     * Handle the DummyModel "updated" event.
     */
    public function updated(DummyModel $model): void
    {
        //
    }

    /**
     * Handle the DummyModel "deleted" event.
     */
    public function deleted(DummyModel $model): void
    {
        //
    }

    /**
     * Handle the DummyModel "restored" event.
     */
    public function restored(DummyModel $model): void
    {
        //
    }

    /**
     * Handle the DummyModel "force deleted" event.
     */
    public function forceDeleted(DummyModel $model): void
    {
        //
    }
}
EOT;
    }

    protected function getPolicyStub()
    {
        return <<<'EOT'
<?php

namespace DummyNamespace;

use App\Models\User;
use App\Models\DummyModel;

class DummyClass
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DummyModel $model): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DummyModel $model): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DummyModel $model): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DummyModel $model): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DummyModel $model): bool
    {
        return true;
    }
}
EOT;
    }

    protected function getRuleStub()
    {
        return <<<'EOT'
<?php

namespace DummyNamespace;

use Nexa\Validation\Rule;

class DummyClass implements Rule
{
    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        // Add validation logic here
        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The :attribute field is invalid.';
    }
}
EOT;
    }

    protected function getJobStub()
    {
        return <<<'EOT'
<?php

namespace DummyNamespace;

use Nexa\Queue\Job;

class DummyClass extends Job
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Add job logic here
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Handle job failure
    }
}
EOT;
    }

    protected function getEventStub()
    {
        return <<<'EOT'
<?php

namespace DummyNamespace;

use Nexa\Events\Event;

class DummyClass extends Event
{
    /**
     * Create a new event instance.
     */
    public function __construct()
    {
        //
    }
}
EOT;
    }

    protected function getListenerStub()
    {
        return <<<'EOT'
<?php

namespace DummyNamespace;

use Nexa\Events\Listener;

class DummyClass extends Listener
{
    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        //
    }
}
EOT;
    }

    protected function getNotificationStub()
    {
        return <<<'EOT'
<?php

namespace DummyNamespace;

use Nexa\Notifications\Notification;

class DummyClass extends Notification
{
    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): array
    {
        return [
            'subject' => 'Notification Subject',
            'message' => 'Notification message here.'
        ];
    }
}
EOT;
    }

    protected function getMailStub()
    {
        return <<<'EOT'
<?php

namespace DummyNamespace;

use Nexa\Mail\Mailable;

class DummyClass extends Mailable
{
    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->subject('Mail Subject')
                    ->view('emails.template');
    }
}
EOT;
    }

    protected function getCommandStub()
    {
        return <<<'EOT'
<?php

namespace DummyNamespace;

use Nexa\Console\Command;

class DummyClass extends Command
{
    protected static $defaultName = 'DummyCommand';

    protected function configure()
    {
        $this->setDescription('Command description');
    }

    protected function handle()
    {
        $this->info('Command executed successfully!');
        return 0;
    }
}
EOT;
    }

    protected function call($command, array $arguments = [])
    {
        $kernel = new Kernel($this->getApplication());
        $input = new ArrayInput(array_merge(['command' => $command], $arguments));
        return $kernel->handle($input, $this->output);
    }
}