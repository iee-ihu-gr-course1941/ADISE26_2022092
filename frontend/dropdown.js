document.addEventListener("DOMContentLoaded" , () =>{

    const dropdowns = document.querySelectorAll(".dropdown");

    dropdowns.forEach(dropdown => {
        const dropdownbtn = dropdown.querySelector(".dropdownbtn");
        const dropdownmenu = dropdown.querySelector(".dropdownmenu");
        const selectedgame = dropdown.closest("form").querySelector(".dropdown-value");
        const items = dropdown.querySelectorAll(".dropdown-item");

        if(dropdownbtn && dropdownmenu){

            dropdownbtn.addEventListener("click" , (e) =>{
                e.stopPropagation();

                document.querySelectorAll(".dropdown-content.show").forEach(openMenu =>{
                    if(openMenu !== dropdownmenu){
                        openMenu.classList.remove("show");
                    } 
                });
                dropdownmenu.classList.toggle("show");
            });

            items.forEach(item =>{
                item.addEventListener("click", () => {
                    dropdownbtn.textContent = item.textContent.trim();
                    selectedgame.value = item.dataset.id;
                    dropdownmenu.classList.remove("show");
                });
            });
        }
    });

    window.addEventListener("click", () => {
        document.querySelectorAll(".dropdown-content.show").forEach(menu => {
            menu.classList.remove("show");
        });
    });
});