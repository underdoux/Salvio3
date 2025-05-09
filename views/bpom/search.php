<?php $this->view('layout/header', ['title' => $title]); ?>

<h1>BPOM Product Search</h1>

<form id="bpom-search-form">
    <input type="text" id="bpom-query" placeholder="Enter product name or registration number" required>
    <button type="submit">Search</button>
</form>

<div id="bpom-results"></div>

<script>
document.getElementById('bpom-search-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const query = document.getElementById('bpom-query').value.trim();
    if (!query) return;

    fetch('<?= url('bpom/search') ?>?query=' + encodeURIComponent(query), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        const resultsDiv = document.getElementById('bpom-results');
        resultsDiv.innerHTML = '';

        if (data.error) {
            resultsDiv.textContent = data.error;
            return;
        }

        if (data.length === 0) {
            resultsDiv.textContent = 'No results found.';
            return;
        }

        const list = document.createElement('ul');
        data.forEach(item => {
            const li = document.createElement('li');
            li.textContent = item.name + ' (Reg No: ' + item.registration_number + ', Category: ' + item.category + ')';
            li.style.cursor = 'pointer';
            li.addEventListener('click', () => {
                fetch('<?= url('bpom/details') ?>/' + encodeURIComponent(item.registration_number), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(resp => resp.json())
                .then(details => {
                    if (details.error) {
                        alert(details.error);
                        return;
                    }
                    // Show details preview or autofill form (to be implemented)
                    alert('Name: ' + details.name + '\\nCategory: ' + details.category + '\\nIngredients: ' + details.ingredients);
                });
            });
            list.appendChild(li);
        });
        resultsDiv.appendChild(list);
    })
    .catch(err => {
        document.getElementById('bpom-results').textContent = 'Error fetching data.';
    });
});
</script>

<?php $this->view('layout/footer'); ?>
