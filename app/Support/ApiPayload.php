<?php

namespace App\Support;

use App\Models\User;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JsonSerializable;

class ApiPayload
{
    public static function clean(mixed $value): mixed
    {
        if ($value instanceof Closure) {
            return null;
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }
        if ($value instanceof User) {
            // Public views may contain an owner relationship: never publish their private account data.
            if (! request()->user()) {
                return $value->only(['id', 'name']);
            }
        }
        if ($value instanceof Paginator) {
            $metadata = $value->toArray();
            $metadata['data'] = self::clean($value->items());

            return self::clean($metadata);
        }
        if ($value instanceof Collection) {
            $value = $value->all();
        }
        if ($value instanceof Model) {
            $model = $value;
            $value = $model->attributesToArray();
            foreach ($model->getRelations() as $name => $relation) {
                if (! in_array($name, $model->getHidden(), true) && (! $model->getVisible() || in_array($name, $model->getVisible(), true))) {
                    $value[Str::snake($name)] = self::clean($relation);
                }
            }
        } elseif ($value instanceof Arrayable) {
            $value = $value->toArray();
        } elseif ($value instanceof JsonSerializable) {
            $value = $value->jsonSerialize();
        } elseif ($value instanceof \stdClass) {
            $value = (array) $value;
        }
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                // Eloquent hidden fields plus a defensive recursive boundary for raw DB projections.
                if (is_string($key) && preg_match('/password|token|secret|recovery_codes|remember_token|otp|private_key|api_key/i', $key)) {
                    continue;
                }
                if ($item instanceof Closure) {
                    continue;
                }
                $result[$key] = self::clean($item);
            }

            return $result;
        }

        return is_scalar($value) || $value === null ? $value : null;
    }
}
