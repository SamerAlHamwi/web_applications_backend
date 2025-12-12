<?php
// app/Repositories/EntityRepository.php
namespace App\Repositories;

use App\Models\Entity;
use Illuminate\Database\Eloquent\Collection;

class EntityRepository
{
    /**
     * Get all entities
     */
    public function all(): Collection
    {
        return Entity::orderBy('name')->get();
    }

    /**
     * Get active entities only
     */
    public function allActive(): Collection
    {
        return Entity::active()->orderBy('name')->get();
    }

    /**
     * Get entities by type
     */
    public function getByType(string $type): Collection
    {
        return Entity::ofType($type)->orderBy('name')->get();
    }

    /**
     * Find entity by ID
     */
    public function findById(int $id): ?Entity
    {
        return Entity::find($id);
    }

    /**
     * Find entity by email
     */
    public function findByEmail(string $email): ?Entity
    {
        return Entity::where('email', $email)->first();
    }

    /**
     * Create entity
     */
    public function create(array $data): Entity
    {
        return Entity::create($data);
    }

    /**
     * Update entity
     */
    public function update(Entity $entity, array $data): bool
    {
        return $entity->update($data);
    }

    /**
     * Soft delete entity
     */
    public function delete(Entity $entity): bool
    {
        return $entity->delete();
    }

    /**
     * Restore soft deleted entity
     */
    public function restore(int $id): bool
    {
        $entity = Entity::withTrashed()->find($id);
        return $entity ? $entity->restore() : false;
    }

    /**
     * Check if email exists
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = Entity::where('email', $email);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get entity with complaints count
     */
    public function findWithComplaintsCount(int $id): ?Entity
    {
        return Entity::withCount('complaints')->find($id);
    }

    /**
     * Get all entities with complaints and employees count
     */
    public function allWithComplaintsCount(): Collection
    {
        return Entity::withCount(['complaints', 'employees'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get entity with employees
     */
    public function findWithEmployees(int $id): ?Entity
    {
        return Entity::with('employees')->find($id);
    }
}
