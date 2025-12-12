<?php

namespace App\Repositories;

use App\Models\ComplaintAttachment;
use Illuminate\Database\Eloquent\Collection;

class ComplaintAttachmentRepository
{
    public function create(array $data): ComplaintAttachment
    {
        return ComplaintAttachment::create($data);
    }

    public function createMany(array $attachments): void
    {
        ComplaintAttachment::insert($attachments);
    }

    public function getByComplaint(int $complaintId): Collection
    {
        return ComplaintAttachment::where('complaint_id', $complaintId)->get();
    }

    public function delete(ComplaintAttachment $attachment): bool
    {
        return $attachment->delete();
    }

    public function countByComplaint(int $complaintId, string $fileType): int
    {
        return ComplaintAttachment::where('complaint_id', $complaintId)
            ->where('file_type', $fileType)
            ->count();
    }
}
