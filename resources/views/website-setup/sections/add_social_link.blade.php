<tr>
    <td>
        <label class="form-label">{{ ___('common.name') }}</label>
        <input class="form-control ot-input mb-5" name="data[name][]"placeholder="{{ ___('common.Enter name') }}">
    </td>
    <td>
        <label class="form-label">{{ ___('common.Icon') }}</label>
        <input class="form-control ot-input mb-5" name="data[icon][]"placeholder="{{ ___('common.Enter icon') }}">
    </td>
    <td>
        <label class="form-label">{{ ___('common.Link') }}</label>
        <div class="d-flex align-items-center mb-5">
            <input class="form-control ot-input mr-2" name="data[link][]"placeholder="{{ ___('common.Enter link') }}">
            <button class="drax_close_icon mark_distribution_close" onclick="removeRow(this)">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </td>
</tr>