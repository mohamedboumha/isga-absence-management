<?php

use App\_Core\Middleware\ActifMiddleware;
use App\_Core\Middleware\RoleMiddleware;
use App\_Core\Services\NotificationService;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
                  ->withRouting(
                      web:      __DIR__ . '/../routes/web.php',
                      commands: __DIR__ . '/../routes/console.php',
                      health:   '/up',
                  )
                  ->withMiddleware(function (Middleware $middleware) : void {
                      $middleware->trustProxies(at: '*');
                      $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

                      $middleware->web(append: [
                                                   HandleAppearance::class,
                                                   HandleInertiaRequests::class,
                                                   AddLinkHeadersForPreloadedAssets::class,
                                                   ActifMiddleware::class,
                                               ]);

                      $middleware->alias([
                                             'role' => RoleMiddleware::class,
                                         ]);
                  })
                  ->withExceptions(function (Exceptions $exceptions) : void {
                      $exceptions->shouldRenderJsonWhen(
                          fn(Request $request) => $request->is('api/*') || $request->expectsJson(),
                      );

                      //================================================================================================
                      // E-mail impossible à envoyer (ex. "Mot de passe oublié ?") : retour au formulaire avec un message
                      // L'erreur reste enregistrée dans le journal de Laravel (report automatique)
                      //================================================================================================
                      $exceptions->render(function (TransportExceptionInterface $exception, Request $request) {
                          if ($request->expectsJson()) {
                              return null;
                          }

                          NotificationService::erreur("L'e-mail n'a pas pu être envoyé. Réessayez dans quelques minutes ; si le problème persiste, contactez la scolarité.");

                          return back()->withInput($request->except(['password', 'password_confirmation', 'current_password']));
                      });

                      //================================================================================================
                      // Pages d'erreur ISGA (Inertia) à la place des pages brutes de Laravel
                      // 500 et 503 : seulement hors mode debug, pour garder le détail de l'erreur en développement
                      //================================================================================================
                      $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
                          $statut = $response->getStatusCode();

                          if ($request->expectsJson()) {
                              return $response;
                          }

                          //============================================================================================
                          // 419 : la page a expiré (session) → on revient en arrière avec un message
                          //============================================================================================
                          if ($statut === 419) {
                              NotificationService::avertissement("La page a expiré. Réessayez votre action.");

                              return back();
                          }

                          $a_afficher = in_array($statut, [403, 404], true)
                              || (in_array($statut, [500, 503], true) && !config('app.debug'));

                          if (!$a_afficher) {
                              return $response;
                          }

                          $message = $statut === 403 && $exception instanceof HttpExceptionInterface
                              ? ($exception->getMessage() ?: null)
                              : null;

                          return Inertia::render('erreur', ['statut' => $statut, 'message' => $message])
                                        ->toResponse($request)
                                        ->setStatusCode($statut);
                      });
                  })
                  ->create();
