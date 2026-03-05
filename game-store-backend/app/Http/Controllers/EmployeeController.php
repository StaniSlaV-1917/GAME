<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    // Список сотрудников (для админки и витрины)
    public function index()
    {
        $employees = Employee::orderBy('id')->get();

        return response()->json($employees);
    }

    // Создать сотрудника
    public function store(Request $request)
    {
        $data = $request->validate([
            // имя из формы -> fullname в БД
            'name'     => 'required|string|max:100',
            // "роль" из формы -> position в БД
            'role'     => 'required|string|max:60',
            // email/контакт -> contact в БД
            'email'    => 'nullable|string|max:255',
        ]);

        $employee = Employee::create([
            'fullname' => $data['name'],
            'position' => $data['role'],
            'contact'  => $data['email'],
        ]);

        return response()->json($employee, 201);
    }

    // Карточка одного сотрудника
    public function show(Employee $employee)
    {
        return response()->json($employee);
    }

    // Обновить сотрудника
    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'role'  => 'required|string|max:60',
            'email' => 'nullable|string|max:255',
        ]);

        $employee->update([
            'fullname' => $data['name'],
            'position' => $data['role'],
            'contact'  => $data['email'],
        ]);

        return response()->json($employee);
    }

    // Удалить сотрудника
    public function destroy(Employee $employee)
    {
        $employee->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
