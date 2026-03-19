<?php

if (! function_exists('tenant_id')) {
    function tenant_id(): int
    {
        $u = auth()->user();

        return $u->owner_user_id ?? $u->id;
    }
}
