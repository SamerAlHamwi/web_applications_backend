<?php
// app/Services/EntityService.php

namespace App\Services;

use App\Models\Entity;
use App\Repositories\EntityRepository;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Collection;

class EntityService
{
    public function __construct(
        private EntityRepository $entityRepository
    ) {}

    /**
     * Get all entities
     */
    public function getAllEntities(): Collection
    {
        return $this->entityRepository->allWithComplaintsCount();
    }

    /**
     * Get active entities only
     */
    public function getActiveEntities(): Collection
    {
        return $this->entityRepository->allActive();
    }

    /**
     * Get entity by ID
     */
    public function getEntityById(int $id): ?Entity
    {
        return $this->entityRepository->findWithComplaintsCount($id);
    }

    /**
     * Create new entity
     */
    public function createEntity(array $data): Entity
    {
        // Check if email already exists
        if ($this->entityRepository->emailExists($data['email'])) {
            throw ValidationException::withMessages([
                'email' => ['This email is already registered.']
            ]);
        }

        return $this->entityRepository->create($data);
    }

    /**
     * Update entity
     */
    public function updateEntity(int $id, array $data): Entity
    {
        $entity = $this->entityRepository->findById($id);

        if (!$entity) {
            throw ValidationException::withMessages([
                'entity' => ['Entity not found.']
            ]);
        }

        // Check email uniqueness (excluding current entity)
        if (isset($data['email']) &&
            $this->entityRepository->emailExists($data['email'], $id)) {
            throw ValidationException::withMessages([
                'email' => ['This email is already in use.']
            ]);
        }

        $this->entityRepository->update($entity, $data);

        return $entity->fresh();
    }

    /**
     * Delete entity (soft delete)
     */
    public function deleteEntity(int $id): bool
    {
        $entity = $this->entityRepository->findById($id);

        if (!$entity) {
            throw ValidationException::withMessages([
                'entity' => ['Entity not found.']
            ]);
        }

        // Check if entity has complaints
        $complaintsCount = $entity->complaints()->count();
        if ($complaintsCount > 0) {
            throw ValidationException::withMessages([
                'entity' => ["Cannot delete entity with {$complaintsCount} associated complaints."]
            ]);
        }

        return $this->entityRepository->delete($entity);
    }

    /**
     * Restore deleted entity
     */
    public function restoreEntity(int $id): bool
    {
        return $this->entityRepository->restore($id);
    }

    /**
     * Toggle entity active status
     */
    public function toggleActiveStatus(int $id): Entity
    {
        $entity = $this->entityRepository->findById($id);

        if (!$entity) {
            throw ValidationException::withMessages([
                'entity' => ['Entity not found.']
            ]);
        }

        $this->entityRepository->update($entity, [
            'is_active' => !$entity->is_active
        ]);

        return $entity->fresh();
    }
}
