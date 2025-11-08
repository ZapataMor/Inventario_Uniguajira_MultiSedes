<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;

class ModelActivityObserver
{
    protected function actorId()
    {
        return Auth::id() ?: null; // si no hay auth, null (sistema)
    }

    protected function tableName(Model $model)
    {
        return $model->getTable();
    }

    protected function recordId(Model $model)
    {
        return $model->getKey();
    }

    protected function shortDetails(Model $model, string $action)
    {
        // construir un mensaje compacto similar a los triggers
        $table = $this->tableName($model);
        $id = $this->recordId($model) ?? 'N/A';
        $summary = "";

        if ($action === 'INSERT') {
            // intenta obtener un nombre representativo si existe
            $label = $model->name ?? ($model->title ?? $model->username ?? null);
            $summary = $label
                ? "Created {$table} '{$label}' (id: {$id})"
                : "Created record in {$table} (id: {$id})";
        } elseif ($action === 'UPDATE') {
            $summary = "Updated {$table} (id: {$id})";
        } elseif ($action === 'DELETE') {
            $label = $model->name ?? ($model->title ?? $model->username ?? null);
            $summary = $label
                ? "Deleted {$table} '{$label}' (id: {$id})"
                : "Deleted record from {$table} (id: {$id})";
        } else {
            $summary = "{$action} on {$table} (id: {$id})";
        }

        // máxima longitud 255
        return mb_substr($summary, 0, 255);
    }

    public function created(Model $model)
    {
        ActivityLog::create([
            'user_id'    => $this->actorId(),
            'action'     => 'INSERT',
            'table_name' => $this->tableName($model),
            'record_id'  => $this->recordId($model),
            'details'    => $this->shortDetails($model, 'INSERT'),
            'created_at' => now(),
        ]);
    }

    public function updated(Model $model)
    {
        ActivityLog::create([
            'user_id'    => $this->actorId(),
            'action'     => 'UPDATE',
            'table_name' => $this->tableName($model),
            'record_id'  => $this->recordId($model),
            'details'    => $this->shortDetails($model, 'UPDATE'),
            'created_at' => now(),
        ]);
    }

    public function deleted(Model $model)
    {
        ActivityLog::create([
            'user_id'    => $this->actorId(),
            'action'     => 'DELETE',
            'table_name' => $this->tableName($model),
            'record_id'  => $this->recordId($model),
            'details'    => $this->shortDetails($model, 'DELETE'),
            'created_at' => now(),
        ]);
    }

    // opcional: restored, forceDeleted, etc.
}
