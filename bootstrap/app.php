<?php

use App\Exceptions\ActiveCropCycleExistsException;
use App\Exceptions\Auth\InactiveAccountException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\InvalidTransitionException;
use App\Exceptions\LastOrganizationAdminRequiredException;
use App\Exceptions\SyncAlreadyRunningException;
use App\Http\Middleware\AttachRequestId;
use App\Http\Middleware\SetLocale;
use App\Support\ApiErrorResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [AttachRequestId::class, SetLocale::class]);
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReportDuplicates();
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $exception): bool => $request->is('api/*')
                || $request->expectsJson(),
        );

        $exceptions->render(function (ValidationException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            $errors = $exception->errors();
            $errorCode = array_key_exists('referral_code', $errors)
                ? 'referral_code_invalid'
                : 'validation_failed';

            return ApiErrorResponse::make(
                $request,
                'Validation failed.',
                $errorCode,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $errors,
            );
        });

        $exceptions->render(fn (AuthenticationException $exception, Request $request): ?JsonResponse => $request->is('api/*') || $request->expectsJson()
            ? ApiErrorResponse::make(
                $request,
                'Unauthenticated.',
                'unauthenticated',
                Response::HTTP_UNAUTHORIZED,
            )
            : null);

        $exceptions->render(fn (AccessDeniedHttpException $exception, Request $request): ?JsonResponse => $request->is('api/*') || $request->expectsJson()
            ? ApiErrorResponse::make(
                $request,
                'This action is unauthorized.',
                'forbidden',
                Response::HTTP_FORBIDDEN,
            )
            : null);

        $exceptions->render(fn (HttpException $exception, Request $request): ?JsonResponse => ($request->is('api/*') || $request->expectsJson()) && $exception->getStatusCode() === Response::HTTP_NOT_FOUND
            ? ApiErrorResponse::make(
                $request,
                'Resource not found.',
                'not_found',
                Response::HTTP_NOT_FOUND,
            )
            : null);

        $exceptions->render(fn (InvalidCredentialsException $exception, Request $request): JsonResponse => ApiErrorResponse::make(
            $request,
            'The provided credentials are invalid.',
            'invalid_credentials',
            Response::HTTP_UNAUTHORIZED,
        ));

        $exceptions->render(fn (InactiveAccountException $exception, Request $request): JsonResponse => ApiErrorResponse::make(
            $request,
            'This account is not active.',
            'account_suspended',
            Response::HTTP_FORBIDDEN,
        ));

        $exceptions->render(fn (ActiveCropCycleExistsException $exception, Request $request): JsonResponse => ApiErrorResponse::make(
            $request,
            $exception->getMessage(),
            'active_cycle_exists',
            Response::HTTP_CONFLICT,
        ));

        $exceptions->render(fn (InvalidTransitionException $exception, Request $request): JsonResponse => ApiErrorResponse::make(
            $request,
            $exception->getMessage(),
            'invalid_transition',
            Response::HTTP_CONFLICT,
        ));

        $exceptions->render(fn (LastOrganizationAdminRequiredException $exception, Request $request): JsonResponse => ApiErrorResponse::make(
            $request,
            $exception->getMessage(),
            'last_admin_required',
            Response::HTTP_CONFLICT,
        ));

        $exceptions->render(fn (SyncAlreadyRunningException $exception, Request $request): JsonResponse => ApiErrorResponse::make(
            $request,
            $exception->getMessage(),
            'sync_already_running',
            Response::HTTP_CONFLICT,
        ));

        $exceptions->render(fn (ThrottleRequestsException $exception, Request $request): JsonResponse => ApiErrorResponse::make(
            $request,
            'Too many requests.',
            'rate_limited',
            Response::HTTP_TOO_MANY_REQUESTS,
            headers: $exception->getHeaders(),
        ));

    })->create();
