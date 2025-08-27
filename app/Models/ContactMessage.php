<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name', 'last_name', 'email', 'phone', 'subject',
        'message', 'is_read', 'replied_at', 'replied_by', 'reply_message',
        'type' // Add this field
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'replied_at' => 'datetime',
    ];

    // Constants for message types
    const TYPE_CONTACT = 'contact';
    const TYPE_NEWSLETTER = 'newsletter';

    // Relationships
    public function repliedBy()
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
}