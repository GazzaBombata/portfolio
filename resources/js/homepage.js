const buttons = document.getElementsByClassName('toggle-button')         
const menu = document.getElementById('mobile-menu')         
menu.style.display = "none";         
for (let i = 0; i < buttons.length; i++) {
  buttons[i].addEventListener('click', function () {            
  if (menu.style.display === "none") 
  {
    menu.style.display = "block";
  } else {
    menu.style.display = "none";        
  };
});
};   