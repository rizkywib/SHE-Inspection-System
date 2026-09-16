<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogActivity
{
    private const LOGGED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    private const SPECIAL_ACTIONS = [
        'login',
        'logout',
        'checkin',
        'sign',
        'investigate',
        'scan',
        'generate',
        'images',
        'items',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $this->record($request, $response);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }

    private function record(Request $request, Response $response): void
    {
        if (!in_array($request->method(), self::LOGGED_METHODS, true)) {
            return;
        }

        $user = $request->user() ?? auth('sanctum')->user();

        $segments = array_values(array_filter(explode('/', trim($request->path(), '/')), fn ($segment) => $segment !== ''));

        if (($segments[0] ?? null) === 'api') {
            array_shift($segments);
        }

        $module = $segments[0] ?? 'unknown';
        $action = $this->resolveAction($request->method(), $segments);

        [$userId, $userName, $userRole] = $this->resolveActor($request, $response, $user);

        ActivityLog::create([
            'user_id' => $userId,
            'user_name' => $userName,
            'user_role' => $userRole,
            'method' => $request->method(),
            'path' => $request->path(),
            'module' => $module,
            'action' => $action,
            'status_code' => $response->getStatusCode(),
            'ip_address' => $request->ip(),
            'user_agent' => $this->truncate((string) $request->userAgent(), 1000),
            'description' => $this->describe($userName, $module, $action),
        ]);
    }

    private function resolveActor(Request $request, Response $response, ?User $user): array
    {
        if ($user) {
            return [$user->id, $user->name, $user->role];
        }

        if ($request->is('api/auth/login') && $response->getStatusCode() < 400) {
            $payload = json_decode((string) $response->getContent(), true);
            $actor = is_array($payload) ? ($payload['user'] ?? null) : null;

            if (is_array($actor)) {
                return [$actor['id'] ?? null, $actor['name'] ?? null, $actor['role'] ?? null];
            }
        }

        return [null, $this->guestName($request), null];
    }

    private function resolveAction(string $method, array $segments): string
    {
        foreach (self::SPECIAL_ACTIONS as $special) {
            if (!in_array($special, $segments, true)) {
                continue;
            }

            return match ($special) {
                'items' => $method === 'POST' ? 'item_create' : ($method === 'DELETE' ? 'item_delete' : 'item_update'),
                'images' => 'image_upload',
                default => $special,
            };
        }

        return match ($method) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'view',
        };
    }

    private function describe(?string $userName, string $module, string $action): string
    {
        $actor = $userName ?: 'Guest';

        return sprintf('%s melakukan "%s" pada modul %s.', $actor, $action, $module);
    }

    private function guestName(Request $request): ?string
    {
        $username = trim((string) $request->input('username', ''));

        return $username !== '' ? $username : 'Guest';
    }

    private function truncate(string $value, int $length): string
    {
        return mb_strlen($value) > $length ? mb_substr($value, 0, $length) : $value;
    }
}
