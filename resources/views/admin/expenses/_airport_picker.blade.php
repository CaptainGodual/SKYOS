{{--
    Airport picker shared by the expense create and edit forms.

    Each selected airport is submitted as its own `airport_ids[]` input, which is
    the array the controller expects. Leaving the picker empty means the expense
    applies to every airport.

    Expects: $selectedAirports — iterable of App\Models\Airport.
--}}
@php($selected = collect($selectedAirports ?? [])->map(fn ($airport) => ['id' => (int) $airport->id, 'identifier' => $airport->identifier])->values())

<div class="airport-search">
    <input class="form-control" id="airport_search" type="text" placeholder="Search airports..." autocomplete="off">
    <div id="airport_results" class="airport-results" style="display: none;"></div>
</div>
<div id="airport_selected" class="d-flex flex-wrap gap-1 mt-2"></div>
<div class="form-text">Select one or more airports where this expense applies. Leave blank for every airport.</div>

<style>
    .airport-search {
        position: relative;
    }
    .airport-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        max-height: 300px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #d2d8e3;
        border-radius: 0.375rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        z-index: 1050;
    }
    .airport-result {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
    }
    .airport-result:hover {
        background-color: #f8fafc;
    }
    .airport-result:last-child {
        border-bottom: none;
    }
</style>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('airport_search');
        const resultsDiv = document.getElementById('airport_results');
        const selectedDiv = document.getElementById('airport_selected');
        const searchUrl = '{{ route('admin.airports.search') }}';
        let selected = @json($selected);
        let timeout;

        function isSelected(id) {
            return selected.some(function (airport) {
                return airport.id === id;
            });
        }

        function renderSelected() {
            selectedDiv.innerHTML = '';

            selected.forEach(function (airport) {
                const chip = document.createElement('span');
                chip.className = 'badge bg-primary-subtle text-primary d-inline-flex align-items-center gap-1';

                const label = document.createElement('span');
                label.textContent = airport.identifier;
                chip.appendChild(label);

                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'airport_ids[]';
                hidden.value = airport.id;
                chip.appendChild(hidden);

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'btn-close';
                remove.style.fontSize = '0.6rem';
                remove.setAttribute('aria-label', 'Remove ' + airport.identifier);
                remove.addEventListener('click', function () {
                    selected = selected.filter(function (item) {
                        return item.id !== airport.id;
                    });
                    renderSelected();
                });
                chip.appendChild(remove);

                selectedDiv.appendChild(chip);
            });
        }

        searchInput.addEventListener('input', function () {
            clearTimeout(timeout);
            const query = this.value.trim();

            if (query.length < 1) {
                resultsDiv.style.display = 'none';
                resultsDiv.innerHTML = '';
                return;
            }

            timeout = setTimeout(async () => {
                try {
                    const response = await fetch(searchUrl + '?query=' + encodeURIComponent(query));
                    const data = await response.json();
                    resultsDiv.innerHTML = '';

                    if (data.results && data.results.length > 0) {
                        data.results.forEach(function (airport) {
                            const div = document.createElement('div');
                            div.className = 'airport-result';
                            div.textContent = airport.text;
                            div.addEventListener('click', function () {
                                if (!isSelected(airport.id)) {
                                    selected.push({ id: airport.id, identifier: airport.identifier });
                                    renderSelected();
                                }

                                searchInput.value = '';
                                resultsDiv.style.display = 'none';
                                resultsDiv.innerHTML = '';
                            });
                            resultsDiv.appendChild(div);
                        });
                        resultsDiv.style.display = 'block';
                    } else {
                        resultsDiv.style.display = 'none';
                    }
                } catch (e) {
                    console.error('Airport search error:', e);
                }
            }, 250);
        });

        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                resultsDiv.style.display = 'none';
            }
        });

        renderSelected();
    });
</script>
@endpush
