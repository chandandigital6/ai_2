<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthorizeBusinessModule;
use App\Support\ApiPayload;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use ReflectionMethod;
use ReflectionNamedType;
use Symfony\Component\HttpFoundation\Response;

class ActionController extends Controller
{
    public function __invoke(Request $request)
    {
        $route = $request->route();
        [$class,$method] = explode('@', $route->getAction('domain_action'));
        $request->attributes->set('domain_action', [$class, $method]);
        // Retain full model-binding semantics of the domain controller behind this JSON adapter.
        $reflection = new ReflectionMethod($class, $method);
        $params = [];
        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();
            $raw = $route->parameter($name);
            $type = $parameter->getType();
            if ($raw === null) {
                continue;
            }
            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin() && is_subclass_of($type->getName(), Model::class)) {
                $model = app($type->getName());
                $field = $route->bindingFieldFor($name);
                $raw = $raw instanceof Model ? $raw : $model->resolveRouteBinding($raw, $field);
                abort_unless($raw, 404);
                $route->setParameter($name, $raw);
            }
            $params[$name] = $raw;
        }
        // Existing controllers use flash messages/import reports. Use an ephemeral session, never cookie authentication.
        $session = new Store('growya_api', new ArraySessionHandler(120));
        $session->start();
        $request->setLaravelSession($session);
        app()->instance('session.store', $session);

        return app(AuthorizeBusinessModule::class)->handle($request, function () use ($request, $class, $method, $params, $session) {
            $result = app()->call([app($class), $method], $params);
            if ($result instanceof View) {
                return response()->json(['data' => ApiPayload::clean($result->getData())]);
            }
            if ($result instanceof RedirectResponse) {
                $errors = $session->get('errors');
                if ($errors) {
                    return response()->json(['message' => 'Validation failed.', 'errors' => $errors->getBag('default')->messages(), 'import_errors' => ApiPayload::clean($session->get('import_errors', []))], 422);
                }

                return response()->json(['message' => $session->get('success', 'Action completed.'), 'location' => $result->getTargetUrl()], $request->isMethod('POST') && $method === 'store' ? 201 : 200);
            }
            if ($result instanceof Response) {
                return $result;
            } // Explicit JSON, PDF, image and CSV responses retain their content type.

            return response()->json(['data' => ApiPayload::clean($result)]);
        });
    }
}
