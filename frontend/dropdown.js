document.addEventListener("DOMContentLoaded" , () =>{

    const dropdownbtn = document.getElementById("dropdownbtn");
    const dropdownmenu = document.getElementById("dropdownmenu");
    const selectedgame = document.getElementById("selectedgame");
    const items = document.querySelectorAll(".dropdown-item");

    if(dropdownbtn && dropdownmenu){

        dropdownbtn.addEventListener("click" , (e) =>{
            e.stopPropagation();
            dropdowncontent.classList.toggle("show");
        });

        items.forEach(item =>{
            item.addEventListener("click", () => {
                dropdownbtn.textContent = item.textContent.trim();
                selectedgame.value = item.dataset.id;
                dropdownmenu.clasList.remove("show");
            });
        });

        window.addEventListener("click", () => {
            dropdownmenu.classList.remove("show");
        })
    }
})