    <!-- ================= VIEW: STAFF DIRECTORY ================= -->
    <div id="view-staff" class="space-y-xl">
        <section class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4">
            <div class="min-w-0">
                <h2 class="font-headline-md text-xl sm:text-headline-md text-primary font-bold">Staff Directory &amp; Logins</h2>
                <p class="font-body-sm text-on-surface-variant">Create and manage salesperson and telecaller corporate profiles and mobile credentials.</p>
            </div>
            <button onclick="toggleModal('modal-add-staff')" class="bg-primary text-on-primary hover:bg-secondary-container hover:text-on-secondary-container px-5 sm:px-6 py-3 rounded-lg font-title-sm flex items-center justify-center gap-2 shadow-md w-full sm:w-auto">
                <span class="material-symbols-outlined">person_add</span> Add Salesperson / Telecaller
            </button>
        </section>

        <section class="bg-white border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-surface-container-low border-b border-outline-variant/30">
                        <tr>
                            <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Name</th>
                            <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Corporate Email</th>
                            <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">System Role</th>
                            <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">City / Branch</th>
                            <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Mobile Login Status</th>
                            <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Created At</th>
                            <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        @php
                            $roleLabels = [
                                'admin' => 'Admin',
                                'sales_executive' => 'Salesperson',
                                'telecaller' => 'Telecaller',
                            ];
                        @endphp
                        @foreach($staffUsers as $staff)
                        <tr class="hover:bg-primary/5 transition-all city-filterable group" data-city="{{ $staff->branch === 'All' ? '' : $staff->branch }}">
                            <td class="px-6 py-4 text-sm font-bold text-primary">{{ $staff->name }}</td>
                            <td class="px-6 py-4 text-sm text-primary font-medium">{{ $staff->email }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded text-[10px] font-bold uppercase tracking-wider {{ $staff->role == 'admin' ? 'bg-error-container text-error' : ($staff->role == 'sales_executive' ? 'bg-primary-fixed text-primary' : 'bg-secondary-fixed text-on-secondary-fixed-variant') }}">
                                    {{ $roleLabels[$staff->role] ?? str_replace('_', ' ', $staff->role) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-on-surface-variant">{{ $staff->branch }}</td>
                            <td class="px-6 py-4 text-xs font-data-tabular text-green-700 font-bold">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> Credentials Active
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs font-data-tabular text-on-surface-variant">{{ $staff->created_at->format('Y-m-d') }}</td>
                            <td class="px-6 py-4 text-right">
                                <button
                                    type="button"
                                    class="js-edit-staff px-3 py-1.5 rounded-lg text-xs font-bold bg-primary text-on-primary hover:bg-secondary transition-all"
                                    data-staff="{{ json_encode([
                                        'id' => $staff->id,
                                        'name' => $staff->name,
                                        'email' => $staff->email,
                                        'role' => $staff->role,
                                        'branch' => $staff->branch,
                                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
                                >
                                    Edit
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
