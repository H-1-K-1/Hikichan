document.addEventListener("DOMContentLoaded", () => {
    const table = document.getElementById("archive-list");
    const headers = table.querySelectorAll("thead th");
    const tbody = table.querySelector("tbody");
    const searchInput = document.getElementById("search-input");

    const sortDirections = {};

    headers.forEach((header, colIndex) => {
        if (colIndex === headers.length - 1) {
            return;
        }

        header.style.cursor = "pointer";

        const icon = document.createElement("i");
        icon.className = "fa fa-sort";
        icon.style.marginLeft = "5px";
        header.appendChild(icon)

        header.addEventListener("click", () => {
            sortDirections[colIndex] = sortDirections[colIndex] === "asc" ? "desc" : "asc";
            sortTableByColumn(colIndex, sortDirections[colIndex]);

            headers.forEach((hdr, idx) => {
                const iconElement = hdr.querySelector("i.fa");
                if (iconElement) {
                    if (idx === colIndex) {
                        iconElement.className = sortDirections[colIndex] === "asc" ? "fa fa-sort-up" : "fa fa-sort-down";
                    } else {
                        iconElement.className = "fa fa-sort";
                    }
                }
            });
        });
    });

    const sortTableByColumn = (columnIndex, direction) => {
        const rowsArray = Array.from(tbody.querySelectorAll("tr"));
        rowsArray.sort((rowA, rowB) => {
            const cellA = rowA.querySelectorAll("td")[columnIndex];
            const cellB = rowB.querySelectorAll("td")[columnIndex];

            const aText = cellA.dataset.sortValue || cellA.textContent.trim();
            const bText = cellB.dataset.sortValue || cellB.textContent.trim();

            const aNum = parseFloat(aText);
            const bNum = parseFloat(bText);

            let compare = 0;
            if (!isNaN(aNum) && !isNaN(bNum)) {
                compare = aNum - bNum;
            } else {
                compare = aText.localeCompare(bText);
            }
            return direction === "asc" ? compare : -compare;
        });

        rowsArray.forEach(row => tbody.appendChild(row));
    }

    if (searchInput) {
        searchInput.addEventListener("keyup", function () {
            const filter = this.value.toLowerCase();
            const rows = tbody.querySelectorAll("tr");
            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                row.style.display = rowText.indexOf(filter) > -1 ? "" : "none";
            });
        });
    }
});
