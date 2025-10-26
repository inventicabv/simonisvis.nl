/*
 *  package: Custom-Quickicons
 *  copyright: Copyright (c) 2024. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 2 or later
 *  link: https://www.joomill-extensions.com
 */

window.onload=function(){
document.addEventListener('subform-row-add', ({detail: {row}}) => {
    setIdentifier(row);
});

let iconField = null;
let previewField = null;

(function () {
    enableSearchField();
    enableIconSelection();
    addModalOpenClickEventListener()
})();
}

function setIdentifier(row) {
    const identifier = new Date().getTime();
    row.querySelector('.quickicon-value-input').setAttribute('data-id', identifier);
    row.querySelector('.icon-preview i').setAttribute('data-preview-id', identifier);
    row.querySelector('.modal-open-btn').setAttribute('data-for', identifier);

    row.querySelector('.modal-open-btn').addEventListener('click', handleModalOpenClick);
}

function addModalOpenClickEventListener() {
    let elements = document.querySelectorAll('.modal-open-btn');
    for (let element of elements) {
        element.addEventListener('click', handleModalOpenClick);
    }
}

function handleModalOpenClick(e) {
    console.log(e.target);
    const identifier = e.target.getAttribute('data-for');
    iconField = document.querySelector('[data-id="'+identifier+'"]');
    previewField = document.querySelector('[data-preview-id="'+identifier+'"]');
};

function enableSearchField() {
    document.getElementById('icon-search-input').addEventListener('keyup', (e) => {
        let elements = document.querySelectorAll('.icon');
        let filter = e.target.value.toLowerCase();

        // Loop through all list items, and hide those who don't match the search query
        for (let element of elements) {
            let iconName = element.getAttribute("data-icon-name");
            if (iconName.toLowerCase().indexOf(filter) > -1) {
                element.style.display = "";
            } else {
                element.style.display = "none";
            }
        }
    });
}

function enableIconSelection() {
    let elements = document.querySelectorAll('.icon');
    for (let element of elements) {
        element.addEventListener('click', (e) => {
            console.log(e.target);
            let iconName = e.target.getAttribute("data-icon-name");
            const classNames = iconName.split(" ");
            iconField.value = iconName;
            previewField.className = "";
            for (let className of classNames) {
                previewField.classList.add(className);
            }
        });
    }
}