<?php

namespace App\Traits;

use Auth;
use Str;

trait HasTable
{
    public $searchField = 'name';

    public $columns = [];
    public $columnsName = [];
    public $sortableColumns = [];

    public $sortBy = 'id';
    public $sortDirection = 'asc';

    public $searchable = true;
    public $editable = true;
    public $deletable = true;
    public $bulkDeletable = true;

    protected function initializeHasTable()
    {
        $this->defineTable();
    }

    public function addColumns($fields, $sortable = [])
    {
        foreach ($fields as $field) {
            $this->addColumn($field, in_array($field, $sortable));
        }
        return $this;
    }

    public function addColumn($field, $isSortable = false)
    {
        $this->columns[] = $field;
        if ($isSortable) {
            $this->addSortableField($field);
        }
        return $this;
    }

    public function addSortableField($field)
    {
        $this->sortableColumns[] = $field;
        return $this;
    }

    public function addColumnName($field, $name)
    {
        $this->columnsName[$field] = $name;
        return $this;
    }

    public function setSortBy($field)
    {
        $this->sortBy = $field;
        return $this;
    }

    public function setSortDirection($field)
    {
        $this->sortDirection = $field;
        return $this;
    }

    public function isSearchable($value)
    {
        $this->searchable = $value;
        return $this;
    }

    public function isEditable($value)
    {
        $this->editable = $value;
        return $this;
    }

    public function isDeletable($value)
    {
        $this->deletable = $value;
        return $this;
    }

    public function isBulkDeletable($value)
    {
        $this->bulkDeletable = $value;
        return $this;
    }

    public function getName()
    {
        return Str::camel(class_basename(static::class));
    }

    public function validatePermission($type)
    {
        if (Auth::check()) {
            return Auth::user()->hasPermissionTo($this->getName() . ':' . $type);
        }
        return false;
    }

    public function getFormRoute()
    {
        $name = $this->getName();

        if (!$this->id) {
            return route("$name.form.create");
        }

        return route("$name.form.update", [
            $name => $this->id,
        ]);
    }

    public function scopeTable($query, $paginate, $sortBy, $sortDirection, $search = '')
    {
        return $query->where($this->searchField, 'like', "%$search%")->orderBy($sortBy, $sortDirection)->paginate($paginate);
    }

    abstract public function defineTable();
}
