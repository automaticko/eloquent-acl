<?php

namespace Automaticko\ACL\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Config\Repository as Config;

class ACL
{
    protected $auth;

    protected $config;

    public function __construct(AuthManager $auth, Config $config)
    {
        $this->auth   = $auth;
        $this->config = $config;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$this->config->get('acl.enabled')) {
            return $next($request);
        }

        if (!$this->auth->check() || !$this->allows($request)) {
            abort(403, trans('acl::acl.errors.forbidden'));
        }

        return $next($request);
    }

    protected function allows(Request $request)
    {
        return $this->auth->user()->can($request->route()->getName());
    }
}
