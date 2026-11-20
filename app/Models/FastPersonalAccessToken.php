<?php

declare(strict_types=1);

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken;

class FastPersonalAccessToken extends PersonalAccessToken
{
    protected $table = 'personal_access_tokens';

    public static function findToken($token)
    {
        if (app()->runningUnitTests()) {
            return parent::findToken($token);
        }

        static $cache = [];
        $now = time();

        if (isset($cache[$token]) && $cache[$token]['exp'] > $now) {
            return $cache[$token]['model'];
        }

        $model = parent::findToken($token);

        if ($model !== null) {
            $model->loadMissing('tokenable');
            $cache[$token] = ['model' => $model, 'exp' => $now + 60];
        }

        return $model;
    }

    public function save(array $options = []): bool
    {
        if ($this->exists && count($this->getDirty()) === 1 && $this->isDirty('last_used_at')) {
            return true;
        }

        return parent::save($options);
    }
}
