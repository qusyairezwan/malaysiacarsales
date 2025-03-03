<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Malaysia Car Sales Dashboard - {{ $year }}</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-3-typeahead/4.0.2/bootstrap3-typeahead.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
</head>

<body>
    <div class="container mt-4">
        <h1 class="text-center">Malaysia Car Sales Dashboard ({{ $year }})</h1>

        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <label for="year" class="form-label">Select Year:</label>
                <input type="number" class="form-control" name="year" value="{{ $year }}" min="2000" max="{{ date('Y') }}">
            </div>
            <div class="col-md-2">
                <label for="month" class="form-label">Month:</label>
                <select name="month" class="form-select">
                    <option value="">All</option>
                    @foreach (range(1, 12) as $m)
                        <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}" {{ request('month') == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="maker" class="form-label">Car Maker:</label>
                <input type="text" class="form-control" id="maker" name="maker" value="{{ $maker }}" placeholder="Enter Maker (Comma Separated)">
            </div>
            <div class="col-md-2">
                <label for="model" class="form-label">Car Model:</label>
                <input type="text" class="form-control" id="model" name="model" value="{{ $model }}" placeholder="Enter Model (Comma Separated)">
            </div>
            <div class="col-md-2">
                <label for="state" class="form-label">State:</label>
                <input type="text" class="form-control" name="state" value="{{ $state }}" placeholder="Enter State">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>

        <hr>

        @if (isset($error))
            <div class="alert alert-danger mt-3">{{ $error }}</div>
        @else
            <div class="row">
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <h3 class="text-center">Car Sales by Brand</h3>
                                    <canvas id="carSalesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 mt-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h3 class="text-center">Car Sales by Colour</h3>
                                <canvas id="carColourChart" style="text-center max-width: 400px; max-height: 400px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h3 class="text-center">Filtered Results</h3>
                            <table id="carSalesTable" class="table table-striped display small">
                                <thead>
                                    <tr>
                                        <th>Brand</th>
                                        <th>Model</th>
                                        <th>Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $totalQty = 0; @endphp
                                    @foreach ($groupedByMakerModel as $maker => $models)
                                        @foreach ($models as $model => $count)
                                            <tr>
                                                <td>{{ $maker }}</td>
                                                <td>{{ $model }}</td>
                                                <td>{{ $count }}</td>
                                            </tr>
                                            @php $totalQty += $count; @endphp
                                        @endforeach
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2" class="text-end">Total:</th>
                                        <th>{{ $totalQty }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                $(document).ready(function() {
                    $('#carSalesTable').DataTable({
                        "pageLength": 25,
                        "lengthChange": false,
                        "paging": true,
                        "ordering": true,
                        "info": true
                    });
                });


                // Ensure makers and models are properly formatted as arrays of strings
                const makers = {!! json_encode(array_keys($groupedByMaker)) !!};

                // Properly extract model names as an array of strings
                const models = {!! json_encode(array_values($groupedByMakerModel)) !!};
                const cleanModels = Object.keys(models).map(maker => Object.keys(models[maker])).flat();

                function setupMultiTypeahead(element, dataList) {
                    $(element).typeahead({
                        source: function(query, process) {
                            if (!Array.isArray(dataList)) {
                                console.error("Expected an array but got:", dataList);
                                return process([]);
                            }

                            // Extract the last word after the comma
                            let lastQuery = query.split(',').pop().trim();
                            if (lastQuery.length === 0) return process([]);

                            let matches = dataList.filter(item => item.toLowerCase().includes(lastQuery.toLowerCase()));
                            process(matches);
                        },
                        matcher: function(item) {
                            if (typeof item !== 'string') {
                                console.warn("Non-string item detected in matcher:", item);
                                return false;
                            }
                            let lastQuery = this.query.split(',').pop().trim();
                            return item.toLowerCase().includes(lastQuery.toLowerCase());
                        },
                        updater: function(item) {
                            let currentValue = $(element).val();
                            let values = currentValue.split(',').map(v => v.trim());
                            values.pop(); // Remove last incomplete word
                            values.push(item); // Add selected suggestion
                            return values.join(', ') + ', '; // Maintain format with a comma
                        },
                        highlighter: function(item) {
                            let lastQuery = this.query.split(',').pop().trim();
                            let regex = new RegExp(`(${lastQuery})`, 'gi');
                            return item.replace(regex, "<strong>$1</strong>"); // Highlight matched part
                        }
                    });
                }

                $(document).ready(function() {
                    setupMultiTypeahead('#maker', makers);
                    setupMultiTypeahead('#model', cleanModels);
                });
                const ctx = document.getElementById('carSalesChart').getContext('2d');
                const groupedByMaker = {!! json_encode($groupedByMaker) !!};
                const groupedByMakerModel = {!! json_encode($groupedByMakerModel) !!};

                const carSalesChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: Object.keys(groupedByMaker),
                        datasets: [{
                            label: 'Number of Cars Registered',
                            data: Object.values(groupedByMaker),
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(tooltipItem) {
                                        let brand = tooltipItem.label;
                                        let models = groupedByMakerModel[brand] || {};
                                        let details = Object.entries(models)
                                            .map(([model, count]) => `${model}: ${count} cars`)
                                            .join('\n');
                                        return `${brand}: ${tooltipItem.raw} cars\n${details}`;
                                    }
                                }
                            }
                        }
                    }
                });

                const colourData = {!! json_encode($groupedByColour) !!};
                const colourMap = {
                    "black": "#000000",
                    "white": "#FFFFF1",
                    "red": "#FF0000",
                    "blue": "#0000FF",
                    "green": "#008000",
                    "silver": "#C0C0C0",
                    "grey": "#808080",
                    "yellow": "#FFFF00",
                    "orange": "#FFA500",
                    "brown": "#A52A2A",
                    "purple": "#800080",
                    "pink": "#FFC0CB",
                    "gold": "#FFD700",
                    "others": "#808080"
                };

                const ctxColour = document.getElementById('carColourChart').getContext('2d');
                new Chart(ctxColour, {
                    type: 'pie',
                    data: {
                        labels: Object.keys(colourData),
                        datasets: [{
                            label: 'Car Colour Distribution',
                            data: Object.values(colourData),
                            backgroundColor: Object.keys(colourData).map(colour => colourMap[colour.toLowerCase()] || '#808080'),
                            borderColor: '#ffffff',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        aspectRatio: 1.5,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(tooltipItem) {
                                        return `${tooltipItem.label}: ${tooltipItem.raw} cars`;
                                    }
                                }
                            }
                        }
                    }
                });
            </script>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <footer class="text-center mt-4 p-3 bg-light">
        <p>Data sourced from <a href="https://data.gov.my/data-catalogue/registration_transactions_car" target="_blank">DOSM (Department of Statistics Malaysia)</a>.</p>
        <p>Developed by Qusyaire Ezwan.</p>
    </footer>
</body>

</html>
