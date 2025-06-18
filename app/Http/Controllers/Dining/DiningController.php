<?php

namespace App\Http\Controllers\Dining;

use App\Http\Controllers\Controller;
use App\Http\Requests\MarkStudentEatenRequest;
use App\Http\Requests\PaginateRequest;
use App\Models\Dining;
use App\Models\Studient;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DiningController extends Controller
{
    use ApiResponse;

    public function index(PaginateRequest $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $hasEaten = $request->input('hasEaten');
            $today = Carbon::now()->format('Y-m-d');

            if ($hasEaten === false || !$hasEaten) {
                // 1. Estudiantes con registro hoy y has_eaten=false
                $realDinings = Dining::with(['studient.grade'])
                    ->whereDate('dining_time', $today)
                    ->where('has_eaten', false)
                    ->get();

                $studentIdsWithoutRecord = Studient::whereDoesntHave('dining', function ($query) use ($today) {
                    $query->whereDate('dining_time', $today);
                })
                    ->pluck('id');

                $fakeDinings = Studient::with('grade')
                    ->whereIn('id', $studentIdsWithoutRecord)
                    ->get()
                    ->map(function ($student) use ($today) {
                        return new Dining([
                            'studient_id' => $student->id,
                            'has_eaten' => false,
                            'dining_time' => $today,
                            'studient' => $student,
                        ]);
                    });

                $combined = $realDinings->concat($fakeDinings)
                    ->sortByDesc('dining_time');

                $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
                    $combined->forPage(\Illuminate\Pagination\Paginator::resolveCurrentPage(), $perPage),
                    $combined->count(),
                    $perPage,
                    \Illuminate\Pagination\Paginator::resolveCurrentPage(),
                    ['path' => $request->url()]
                );

                return $this->successResponse($paginated, 'Estudiantes que no han comido hoy obtenidos exitosamente.');
            }

            $query = Dining::with(['studient.grade'])
                ->whereDate('dining_time', $today);

            if ($hasEaten !== null) {
                $query->where('has_eaten', $hasEaten);
            }

            $dinings = $query->orderBy('dining_time', 'desc')
                ->paginate($perPage);

            $message = 'Registros de comedor obtenidos exitosamente.';
            if ($hasEaten !== null) {
                $statusText = $hasEaten ? 'que han comido' : 'que no han comido';
                $message = "Estudiantes {$statusText} hoy obtenidos exitosamente.";
            }

            return $this->successResponse($dinings, $message);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener los registros: ' . $e->getMessage(), 500);
        }
    }

    public function todayStats()
    {
        try {
            $today = Carbon::now()->format('Y-m-d');

            $totalStudents = Studient::count();
            $studentsWhoAte = Dining::whereDate('dining_time', $today)
                ->where('has_eaten', true)
                ->count();
            $studentsWhoDidntEat = Studient::whereIn('id', function ($query) use ($today) {
                $query->select('studient_id')
                    ->from('dinings')
                    ->whereDate('dining_time', $today)
                    ->where('has_eaten', false);
            })
                ->orWhereNotIn('id', function ($query) use ($today) {
                    $query->select('studient_id')
                        ->from('dinings')
                        ->whereDate('dining_time', $today);
                })
                ->count();

            $stats = [
                'date' => $today,
                'total_students' => $totalStudents,
                'students_who_ate' => $studentsWhoAte,
                'students_who_didnt_eat' => $studentsWhoDidntEat,
                'attendance_percentage' => $totalStudents > 0 ? round(($studentsWhoAte / $totalStudents) * 100, 2) : 0
            ];

            return $this->successResponse($stats, 'Estadísticas del día obtenidas exitosamente.');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener las estadísticas: ' . $e->getMessage(), 500);
        }
    }

    public function markAsEaten(MarkStudentEatenRequest $request)
    {
        try {
            $studientId = $request->input('studient_id');
            $today = Carbon::now()->format('Y-m-d');
            $now = Carbon::now();

            DB::beginTransaction();

            $studient = Studient::with('grade')->find($studientId);
            if (!$studient) {
                return $this->errorResponse('Estudiante no encontrado.', 404);
            }

            $existingDining = Dining::where('studient_id', $studientId)
                ->whereDate('dining_time', $today)
                ->first();

            if ($existingDining) {
                if ($existingDining->has_eaten) {
                    DB::rollBack();
                    return $this->errorResponse(
                        'El estudiante ya fue marcado como que almorzó hoy a las ' .
                            $existingDining->dining_time->format('H:i:s') . '.',
                        409
                    );
                } else {
                    $existingDining->update([
                        'has_eaten' => true,
                        'dining_time' => $now
                    ]);
                    $dining = $existingDining->fresh(['studient.grade']);
                }
            } else {
                $dining = Dining::create([
                    'studient_id' => $studientId,
                    'has_eaten' => true,
                    'dining_time' => $now
                ]);
                $dining->load(['studient.grade']);
            }

            DB::commit();

            $studentName = $studient->name . ' ' . $studient->last_name;
            $grade = $studient->grade ? $studient->grade->year . $studient->grade->section : 'Sin grado';

            return $this->successResponse([
                'dining' => $dining,
                'student_info' => [
                    'name' => $studentName,
                    'ci' => $studient->ci,
                    'grade' => $grade,
                    'dining_time' => $now->format('Y-m-d H:i:s')
                ]
            ], "Estudiante {$studentName} marcado como almorzado exitosamente.");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al marcar el estudiante: ' . $e->getMessage(), 500);
        }
    }

    public function markAsNotEaten(MarkStudentEatenRequest $request)
    {
        try {
            $studientId = $request->input('studient_id');
            $today = Carbon::now()->format('Y-m-d');

            DB::beginTransaction();

            $studient = Studient::with('grade')->find($studientId);
            if (!$studient) {
                return $this->errorResponse('Estudiante no encontrado.', 404);
            }

            $dining = Dining::where('studient_id', $studientId)
                ->whereDate('dining_time', $today)
                ->first();

            if (!$dining) {
                DB::rollBack();
                return $this->errorResponse('No se encontró registro de comedor para hoy.', 404);
            }

            if (!$dining->has_eaten) {
                DB::rollBack();
                return $this->errorResponse('El estudiante ya está marcado como que no ha almorzado.', 409);
            }

            $dining->update([
                'has_eaten' => false,
                'dining_time' => Carbon::now()
            ]);

            DB::commit();

            $studentName = $studient->name . ' ' . $studient->last_name;

            return $this->successResponse([
                'dining' => $dining->fresh(['studient.grade']),
                'student_info' => [
                    'name' => $studentName,
                    'ci' => $studient->ci,
                    'grade' => $studient->grade ? $studient->grade->year . $studient->grade->section : 'Sin grado'
                ]
            ], "Estudiante {$studentName} marcado como NO almorzado.");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al actualizar el estudiante: ' . $e->getMessage(), 500);
        }
    }
}
