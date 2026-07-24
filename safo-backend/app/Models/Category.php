<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Category extends Model
{
    use HasSlug;

    protected $fillable = [
        'parent_id', 'name', 'name_en', 'slug', 'description',
        'image', 'icon', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug');
    }

    public function parent()    { return $this->belongsTo(Category::class, 'parent_id'); }
    public function children()  { return $this->hasMany(Category::class, 'parent_id'); }
    public function products()  { return $this->hasMany(Product::class); }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeRoot($query)   { return $query->whereNull('parent_id'); }
    public function scopeOrdered($query){ return $query->orderBy('sort_order'); }
}
