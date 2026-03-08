<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $table = 'news';

    public $timestamps = false;

    protected $fillable = [
        'title',
        'content',
        'image',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Get the full URL for the news article's image.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getImageAttribute($value)
    {
        // Если в базе хранится путь к файлу, мы конструируем полный URL с помощью хелпера asset().
        // Это работает благодаря символической ссылке, создаваемой командой `php artisan storage:link`.
        return $value ? asset('storage/' . $value) : null;
    }
}
