<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmployeeController extends Controller
{
    /**
     * Преобразует модель Employee в формат, ожидаемый фронтендом.
     *
     * @param Employee $employee
     * @return array
     */
    private function transformEmployee(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'fullname' => $employee->fullname,
            'email' => $employee->contact, // Преобразуем contact в email
            'role' => $employee->position,  // Преобразуем position в role
            'created_at' => null, // Добавляем недостающее поле
            // Мы не можем вернуть реальный created_at, так как его нет в таблице
            // Оставляем как null, чтобы фронтенд не падал
        ];
    }

    /**
     * Список сотрудников.
     */
    public function index(): JsonResponse
    {
        $employees = Employee::orderBy('id')->get();

        // Трансформируем коллекцию
        $transformedEmployees = $employees->map(function ($employee) {
            return $this->transformEmployee($employee);
        });

        return response()->json($transformedEmployees);
    }

    /**
     * Создать сотрудника.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fullname' => 'required|string|max:100',
            'role'     => 'required|string|max:60',
            'email'    => 'nullable|string|max:255',
        ]);

        $employee = Employee::create([
            'fullname' => $data['fullname'],
            'position' => $data['role'], // Сохраняем 'role' из формы в 'position' в БД
            'contact'  => $data['email'],  // Сохраняем 'email' из формы в 'contact' в БД
        ]);

        return response()->json($this->transformEmployee($employee), 201);
    }

    /**
     * Карточка одного сотрудника.
     */
    public function show(Employee $employee): JsonResponse
    {
        return response()->json($this->transformEmployee($employee));
    }

    /**
     * Обновить сотрудника.
     */
    public function update(Request $request, Employee $employee): JsonResponse
    {
        $data = $request->validate([
            'fullname' => 'required|string|max:100',
            'role'     => 'required|string|max:60',
            'email'    => 'nullable|string|max:255',
        ]);

        $employee->update([
            'fullname' => $data['fullname'],
            'position' => $data['role'],
            'contact'  => $data['email'],
        ]);

        return response()->json($this->transformEmployee($employee));
    }

    /**
     * Удалить сотрудника.
     */
    public function destroy(Employee $employee): JsonResponse
    {
        $employee->delete();
        return response()->json(null, 204);
    }
}
