{{--
    Shared, categorized permission picker — used by roles/create, roles/edit
    and users/change_permission so all three stay consistent. Renders one
    collapsible section per category (grouped via group_permissions_by_category()
    in app/Helpers/common-helpers.php, same categories as the sidebar) with a
    bulk "select all in this category" checkbox, plus page-level Select All /
    Clear All buttons.

    Expects:
      $permissionGroups    Collection<string, Collection<Permission>> (required)
      $selectedPermissions array of already-granted keyword strings (optional)
--}}
@php($selectedPermissions = $selectedPermissions ?? [])

@push('css')
    <style>
        .permission-category-header {
            cursor: pointer;
            padding: 10px 14px;
            border: 1px solid #eaeaea;
            border-radius: 5px;
            background-color: var(--ot-bg-primary, #fff);
            color: var(--ot-text-primary);
        }
        .permission-category-header .permission-category-caret {
            transition: transform .2s ease;
            font-size: 12px;
            opacity: .6;
        }
        .permission-category-header[aria-expanded="true"] .permission-category-caret {
            transform: rotate(180deg);
        }
        .permission-category .table-responsive {
            border: 1px solid #eaeaea;
            border-top: none;
            border-radius: 0 0 5px 5px;
            padding: 0 14px;
        }
    </style>
@endpush

<div class="table-container mt-20 role-permisssion-control" id="permissionPicker">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <strong>{{ ___('users_roles.Permissions') }}</strong>
        <div>
            <button type="button" class="btn btn-sm ot-btn-primary" data-permission-bulk="all">{{ ___('users_roles.select_all') }}</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-permission-bulk="none">{{ ___('users_roles.clear_all') }}</button>
        </div>
    </div>

    @foreach ($permissionGroups as $category => $permissions)
        @php($groupId = 'perm-group-' . $loop->index)
        @php($groupHasSelection = $permissions->contains(fn ($permission) => count(array_intersect(array_filter((array) $permission->keywords), $selectedPermissions)) > 0))
        <div class="permission-category mb-2">
            <div class="permission-category-header d-flex align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#{{ $groupId }}" role="button" aria-expanded="{{ $groupHasSelection ? 'true' : 'false' }}">
                <div class="form-check d-flex align-items-center mb-0" onclick="event.stopPropagation()">
                    <input type="checkbox" class="form-check-input permission-category-toggle" data-group="{{ $groupId }}" id="{{ $groupId }}-toggle">
                    <label class="form-check-label fw-bold ms-2 mb-0" for="{{ $groupId }}-toggle">{{ $category }}</label>
                </div>
                <i class="fa-solid fa-chevron-down permission-category-caret"></i>
            </div>
            <div class="collapse {{ $groupHasSelection ? 'show' : '' }}" id="{{ $groupId }}">
                <div class="table-responsive">
                    <table class="ot-basic-table ot-table-bg">
                        <tbody>
                            @foreach ($permissions as $permission)
                                <tr>
                                    <td>{{ ___('users_roles.' . $permission->attribute) }}</td>
                                    <td>
                                        <div class="permission-list-td">
                                            @foreach ($permission->keywords as $key => $keyword)
                                                @if ($keyword != '')
                                                    <div class="input-check-radio">
                                                        <div class="form-check d-flex align-items-center">
                                                            <input type="checkbox"
                                                                class="form-check-input mr-4 common-key permission-item"
                                                                data-group="{{ $groupId }}"
                                                                name="permissions[]"
                                                                value="{{ $keyword }}"
                                                                id="{{ $keyword }}"
                                                                {{ in_array($keyword, $selectedPermissions) ? 'checked' : '' }}>
                                                            <label class="custom-control-label" for="{{ $keyword }}">{{ ___('users_roles.' . $key) }}</label>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</div>

@push('script')
    <script>
        (function () {
            var picker = document.getElementById('permissionPicker');
            if (!picker || picker.dataset.bound) {
                return;
            }
            picker.dataset.bound = '1';

            function itemsFor(groupId) {
                return picker.querySelectorAll('.permission-item[data-group="' + groupId + '"]');
            }

            function refreshToggle(groupId) {
                var toggle = picker.querySelector('.permission-category-toggle[data-group="' + groupId + '"]');
                if (!toggle) {
                    return;
                }
                var items = itemsFor(groupId);
                var checked = Array.prototype.filter.call(items, function (item) { return item.checked; }).length;
                toggle.checked = items.length > 0 && checked === items.length;
                toggle.indeterminate = checked > 0 && checked < items.length;
            }

            function refreshAllToggles() {
                picker.querySelectorAll('.permission-category-toggle').forEach(function (toggle) {
                    refreshToggle(toggle.getAttribute('data-group'));
                });
            }

            picker.addEventListener('change', function (event) {
                if (event.target.classList.contains('permission-category-toggle')) {
                    var groupId = event.target.getAttribute('data-group');
                    itemsFor(groupId).forEach(function (item) { item.checked = event.target.checked; });
                    event.target.indeterminate = false;
                    return;
                }
                if (event.target.classList.contains('permission-item')) {
                    refreshToggle(event.target.getAttribute('data-group'));
                }
            });

            picker.querySelectorAll('[data-permission-bulk]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var checkAll = button.getAttribute('data-permission-bulk') === 'all';
                    picker.querySelectorAll('.permission-item').forEach(function (item) { item.checked = checkAll; });
                    refreshAllToggles();
                });
            });

            refreshAllToggles();
        })();
    </script>
@endpush
