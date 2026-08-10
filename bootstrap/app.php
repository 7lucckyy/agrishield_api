<?php

use App\Exceptions\Auth\InactiveAccountException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Http\Middleware\AttachRequestId;
use App\Http\Middleware\SetLocale;
use App\Support\ApiErrorResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [AttachRequestId::class, SetLocale::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReportDuplicates();
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $exception): bool => $request->is('api/*')
                || $request->expectsJson(),
        );

        $exceptions->render(fn (ValidationException $exception, Request $request): JsonResponse => ApiErrorResponse::make(
            $request,
            'Validation failed.',
            'validation_failed',
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $exception->errors(),
        ));

        $exceptions->render(fn (AuthenticationException $exception, Request $request): JsonResponse => ApiErrorResponse::make(
            $request,
            'Unauthenticated.',
            'unauthenticated',
            Response::HTTP_UNAUTHORIZED,
        ));

        $exceptions->render(fn (AuthorizationException $exception, Request $request): JsonResponse => ApiErrorResponse::make(
            $request,
            'This action is unauthorized.',
            'forbidden',
            Response::HTTP_FORBIDDEN,
        ));

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

        $exceptions->render(fn (ThrottleRequestsException $exception, Request $request): JsonResponse => ApiErrorResponse::make(
            $request,
            'Too many requests.',
            'rate_limited',
            Response::HTTP_TOO_MANY_REQUESTS,
            headers: $exception->getHeaders(),
        ));

        $exceptions->render(fn (NotFoundHttpException $exception, Request $request): JsonResponse => ApiErrorResponse::make(
            $request,
            'Resource not found.',
            'not_found',
            Response::HTTP_NOT_FOUND,
        ));
    })->create();
