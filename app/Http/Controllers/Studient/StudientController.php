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
        $studients = Studient::with(['grade', 'dining'])->latest('created_at')
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
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        try {
            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Verificar estructura del documento
            if (count($rows) < 4) {
                return $this->errorResponse('El documento proporcionado no tiene la estructura correcta!', 400);
            }

            $headerRow = $rows[2]; // Fila 3 (índice 2)
            $expectedHeaders = ['CEDULA', 'APELLIDOS', 'NOMBRES', 'GRADO', 'SEXO'];
            $actualHeaders = [
                strtoupper(trim($headerRow[1] ?? '')), // Columna B
                strtoupper(trim($headerRow[2] ?? '')), // Columna C
                strtoupper(trim($headerRow[3] ?? '')), // Columna D
                strtoupper(trim($headerRow[4] ?? '')), // Columna E
                strtoupper(trim($headerRow[5] ?? '')), // Columna F
            ];

            if ($actualHeaders !== $expectedHeaders) {
                return $this->errorResponse('El documento proporcionado no tiene la estructura correcta!', 400);
            }

            $studentsCreated = 0;
            $studentsUpdated = 0;
            $errors = [];

            DB::beginTransaction();

            Studient::query()->delete();
            Grade::query()->delete();

            for ($i = 3; $i < count($rows); $i++) {
                $row = $rows[$i];

                if (empty($row[1]) || empty($row[2]) || empty($row[3])) {
                    continue;
                }

                try {
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

                    $year = substr($grado, 0, 1); // Primer carácter
                    $section = substr($grado, 1, 1); // Segundo carácter

                    // Buscar o crear el grado
                    $grade = Grade::firstOrCreate([
                        'year' => $year,
                        'section' => $section,
                    ]);

                    $gender = strtoupper($sexo) === 'M' ? 'M' : 'F';

                    $studient = Studient::where('ci', $cedula)->first();

                    $studientData = [
                        'grade_id' => $grade->id,
                        'name' => trim($nombres),
                        'last_name' => trim($apellidos),
                        'ci' => $cedula,
                        'gender' => $gender,
                    ];

                    if ($studient) {
                        $studient->update($studientData);
                        $studentsUpdated++;
                    } else {
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
            $studients = Studient::where('ci', 'like', $request->search . '%')
                ->orderBy('id', 'desc')
                ->take(10)
                ->get();
        }

        return $this->successResponse($studients, 'Studients retrieved successfully.');
    }
}
