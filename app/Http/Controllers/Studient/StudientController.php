<?php

namespace App\Http\Controllers\Studient;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use App\Http\Requests\StoreStudientRequest;
use App\Models\Grade;
use App\Models\Studient;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StudientController extends Controller
{
    use ApiResponse;

    public function index(PaginateRequest $request)
    {
        $perPage = $request->input('per_page', 10);
        $studients = Studient::latest('created_at')
            ->paginate($perPage);

        return $this->successResponse($studients, 'Studients retrieved successfully.');
    }

    public function show($id)
    {
        $studient = Studient::find($id);
        if (!$studient) {
            return $this->errorResponse('studient not found.', 404);
        }
        return $this->successResponse($studient, 'studient retrieved successfully.');
    }

    public function store(Request $request)
    {
        // Validar que se haya enviado un archivo Excel
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        try {
            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $studentsCreated = 0;
            $studentsUpdated = 0;
            $errors = [];

            DB::beginTransaction();

            // Empezar desde la fila 4 (índice 3) para saltar los headers
            for ($i = 3; $i < count($rows); $i++) {
                $row = $rows[$i];

                // Verificar que la fila no esté vacía
                if (empty($row[1]) || empty($row[2]) || empty($row[3])) {
                    continue;
                }

                try {
                    // Extraer datos de la fila
                    $cedula = $row[1]; // Columna B - CEDULA
                    $apellidos = $row[2]; // Columna C - APELLIDOS
                    $nombres = $row[3]; // Columna D - NOMBRES
                    $grado = $row[4]; // Columna E - GRADO
                    $sexo = $row[5]; // Columna F - SEXO

                    // Validar que los campos requeridos no estén vacíos
                    if (empty($cedula) || empty($apellidos) || empty($nombres) || empty($grado)) {
                        $errors[] = "Fila " . ($i + 1) . ": Datos incompletos";
                        continue;
                    }

                    // Extraer año y sección del grado (ej: "1F" -> año="1", sección="F")
                    $year = substr($grado, 0, 1); // Primer carácter
                    $section = substr($grado, 1, 1); // Segundo carácter

                    // Buscar o crear el grado
                    $grade = Grade::firstOrCreate([
                        'year' => $year,
                        'section' => $section,
                    ]);

                    // Normalizar el género
                    $gender = strtoupper($sexo) === 'M' ? 'M' : 'F';

                    // Buscar si el estudiante ya existe por cédula
                    $studient = Studient::where('ci', $cedula)->first();

                    $studientData = [
                        'grade_id' => $grade->id,
                        'name' => trim($nombres),
                        'last_name' => trim($apellidos),
                        'ci' => $cedula,
                        'gender' => $gender,
                    ];

                    if ($studient) {
                        // Actualizar estudiante existente
                        $studient->update($studientData);
                        $studentsUpdated++;
                    } else {
                        // Crear nuevo estudiante
                        Studient::create($studientData);
                        $studentsCreated++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Fila " . ($i + 1) . ": " . $e->getMessage();
                }
            }

            DB::commit();

            return $this->successResponse(null, 'Estudiantes creados correctamente', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al procesar el archivo: ' . $e->getMessage(), 500);
        }
    }

    public function delete($id)
    {
        $studient = Studient::find($id);
        if (!$studient) {
            return $this->errorResponse('studient not found.', 404);
        }
        $studient->delete();
        return $this->successResponse(null, 'studient deleted successfully.');
    }

    public function search(Request $request)
    {
        $studients = Studient::where('name', 'like', '%' . $request->search . '%')
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        if ($studients->isEmpty()) {
            $studients = Studient::where('ci', 'like', '%' . $request->search . '%')
                ->orderBy('id', 'desc')
                ->take(10)
                ->get();
        }

        return $this->successResponse($studients, 'Studients retrieved successfully.');
    }
}
