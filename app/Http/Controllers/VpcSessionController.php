<?php

// file: app/Http/Controllers/VpcSessionController.php

namespace App\Http\Controllers;

use App\Models\VpcSession;
use App\Services\Vpc\VpcManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VpcSessionController extends Controller
{
    public function __construct(
        protected VpcManager $vpcManager
    ) {}

    /**
     * Показать список VPC-сессий пользователя.
     */
    public function index()
    {
        $user = Auth::user();

        $sessions = VpcSession::where('user_id', $user->id)
            ->with(['company', 'agentTask'])
            ->latest('updated_at')
            ->paginate(20);

        return view('vpc_sessions.index', compact('sessions'));
    }

    /**
     * Показать форму создания новой сессии.
     */
    public function create()
    {
        $user = Auth::user();
        $companies = $user->companies ?? collect();
        $agentTasks = $user->agentTasks()->latest()->limit(20)->get();

        return view('vpc_sessions.create', compact('companies', 'agentTasks'));
    }

    /**
     * Создать новую VPC-сессию.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'agent_task_id' => ['nullable', 'exists:agent_tasks,id'],
        ]);

        $session = $this->vpcManager->createSession($user, [
            'name' => $data['name'] ?? null,
            'company_id' => $data['company_id'] ?? null,
            'agent_task_id' => $data['agent_task_id'] ?? null,
        ]);

        // TODO: здесь можно отправить запрос во внешнюю систему на запуск VM
        // Пока сразу запускаем (симуляция)
        $this->vpcManager->startSession($session);

        return redirect()->route('vpc_sessions.show', $session)
            ->with('status', 'VPC-сессия создана и запущена.');
    }

    /**
     * Показать детали сессии.
     */
    public function show(VpcSession $vpcSession)
    {
        $this->authorizeSession($vpcSession);

        $actions = $vpcSession->actions()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        $snapshots = $vpcSession->snapshots()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('vpc_sessions.show', [
            'session' => $vpcSession,
            'actions' => $actions,
            'snapshots' => $snapshots,
        ]);
    }

    /**
     * Запустить сессию.
     */
    public function start(VpcSession $vpcSession)
    {
        $this->authorizeSession($vpcSession);

        if ($vpcSession->isRunning()) {
            return redirect()->route('vpc_sessions.show', $vpcSession)
                ->with('error', 'Сессия уже запущена.');
        }

        $this->vpcManager->startSession($vpcSession);

        return redirect()->route('vpc_sessions.show', $vpcSession)
            ->with('status', 'Сессия запущена.');
    }

    /**
     * Остановить сессию.
     */
    public function stop(VpcSession $vpcSession)
    {
        $this->authorizeSession($vpcSession);

        $this->vpcManager->stopSession($vpcSession);

        // TODO: отправить внешней системе команду на остановку VM

        return redirect()->route('vpc_sessions.show', $vpcSession)
            ->with('status', 'Сессия остановлена.');
    }

    /**
     * Поставить сессию на паузу.
     */
    public function pause(VpcSession $vpcSession)
    {
        $this->authorizeSession($vpcSession);

        $this->vpcManager->pauseSession($vpcSession);

        return redirect()->route('vpc_sessions.show', $vpcSession)
            ->with('status', 'Сессия приостановлена.');
    }

    /**
     * Возобновить сессию.
     */
    public function resume(VpcSession $vpcSession)
    {
        $this->authorizeSession($vpcSession);

        $this->vpcManager->resumeSession($vpcSession);

        return redirect()->route('vpc_sessions.show', $vpcSession)
            ->with('status', 'Сессия возобновлена.');
    }

    /**
     * Изменить режим управления.
     */
    public function setControlMode(VpcSession $vpcSession, Request $request)
    {
        $this->authorizeSession($vpcSession);

        $data = $request->validate([
            'mode' => ['required', 'in:AGENT_CONTROL,USER_CONTROL,PAUSED'],
        ]);

        $this->vpcManager->setControlMode($vpcSession, $data['mode']);

        $modeNames = [
            'AGENT_CONTROL' => 'Управление агентом',
            'USER_CONTROL' => 'Ручное управление',
            'PAUSED' => 'Пауза',
        ];

        return redirect()->route('vpc_sessions.show', $vpcSession)
            ->with('status', 'Режим изменён на: '.$modeNames[$data['mode']]);
    }

    /**
     * Удалить сессию.
     */
    public function destroy(VpcSession $vpcSession)
    {
        $this->authorizeSession($vpcSession);

        // Сначала останавливаем, если запущена
        if ($vpcSession->isRunning()) {
            $this->vpcManager->stopSession($vpcSession);
        }

        $vpcSession->delete();

        return redirect()->route('vpc_sessions.index')
            ->with('status', 'Сессия удалена.');
    }

    /**
     * Проверить, что сессия принадлежит текущему пользователю.
     */
    protected function authorizeSession(VpcSession $session): void
    {
        $user = Auth::user();

        if ($session->user_id !== $user->id) {
            abort(403);
        }
    }
}
