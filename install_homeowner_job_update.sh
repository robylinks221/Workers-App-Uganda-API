#!/bin/zsh
set -e

API_DIR="${1:-$HOME/Development/worker_app_api}"
ROUTES_FILE="$API_DIR/routes/api.php"
CONTROLLER_FILE="$API_DIR/app/Http/Controllers/Api/HomeownerJobController.php"

if [[ ! -f "$ROUTES_FILE" ]]; then
  echo "Could not find: $ROUTES_FILE"
  exit 1
fi

if [[ ! -f "$CONTROLLER_FILE" ]]; then
  echo "Could not find: $CONTROLLER_FILE"
  exit 1
fi

cp "$ROUTES_FILE" "$ROUTES_FILE.before_job_update"
cp "$CONTROLLER_FILE" "$CONTROLLER_FILE.before_job_update"

python3 - "$ROUTES_FILE" "$CONTROLLER_FILE" <<'PY'
from pathlib import Path
import re
import sys

routes_path = Path(sys.argv[1])
controller_path = Path(sys.argv[2])

routes = routes_path.read_text()
controller = controller_path.read_text()

route_block = """    Route::put('/homeowner/jobs/{job}', [
        HomeownerJobController::class,
        'update',
    ]);

"""

delete_route = """    Route::delete('/homeowner/jobs/{job}', [
        HomeownerJobController::class,
        'destroy',
    ]);
"""

if "Route::put('/homeowner/jobs/{job}'" not in routes:
    if delete_route not in routes:
        raise SystemExit("Could not find homeowner job delete route.")
    routes = routes.replace(delete_route, route_block + delete_route, 1)

if re.search(r"public function update\s*\(", controller) is None:
    update_method = r"""
    /**
     * Update a job owned by the logged-in homeowner.
     */
    public function update(Request $request, Job $job)
    {
        if ($job->homeowner_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot update this job.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'service_category_ids' => ['nullable', 'array'],
            'service_category_ids.*' => [
                'integer',
                'exists:service_categories,id',
            ],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'address' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'start_time' => ['nullable'],
            'work_arrangement' => ['nullable', 'string', 'max:100'],
            'contract_duration' => ['nullable', 'string', 'max:100'],
            'duration' => ['nullable', 'string', 'max:100'],
            'budget_type' => ['required', 'in:fixed,daily,monthly'],
            'budget_amount' => ['required', 'numeric', 'min:1000'],
            'accommodation_provided' => ['nullable', 'boolean'],
            'meals_provided' => ['nullable', 'boolean'],
            'transport_allowance' => ['nullable', 'boolean'],
            'medical_support' => ['nullable', 'boolean'],
            'uniform_provided' => ['nullable', 'boolean'],
            'other_benefits' => ['nullable', 'string'],
            'is_urgent' => ['nullable', 'boolean'],
        ]);

        $updates = [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'address' => $validated['address'],
            'district' => $validated['district'],
            'start_date' => $validated['start_date'],
            'start_time' => $validated['start_time'] ?? null,
            'budget_type' => $validated['budget_type'],
            'budget_amount' => $validated['budget_amount'],
            'is_urgent' => $validated['is_urgent'] ?? false,
        ];

        $optionalColumns = [
            'category',
            'work_arrangement',
            'contract_duration',
            'duration',
            'accommodation_provided',
            'meals_provided',
            'transport_allowance',
            'medical_support',
            'uniform_provided',
            'other_benefits',
        ];

        foreach ($optionalColumns as $column) {
            if (
                array_key_exists($column, $validated) &&
                \Illuminate\Support\Facades\Schema::hasColumn(
                    $job->getTable(),
                    $column
                )
            ) {
                $updates[$column] = $validated[$column];
            }
        }

        $job->update($updates);

        if (
            isset($validated['service_category_ids']) &&
            method_exists($job, 'serviceCategories')
        ) {
            $job->serviceCategories()->sync(
                $validated['service_category_ids']
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Job updated successfully.',
            'job' => $job->fresh(),
        ]);
    }

"""

    marker = "    /**\n     * Delete a job."
    if marker not in controller:
        raise SystemExit("Could not find destroy method marker.")
    controller = controller.replace(marker, update_method + marker, 1)

routes_path.write_text(routes)
controller_path.write_text(controller)
PY

cd "$API_DIR"
php artisan route:clear
php artisan optimize:clear
php artisan route:list --path=api/homeowner/jobs
