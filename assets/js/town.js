(function () {
  var address = document.querySelector("[data-address]");
  var town = document.querySelector("[data-town]");
  if (!address || !town) {
    return;
  }
  var towns = ["Hahira", "Lake Park", "Remerton", "Valdosta"];
  function detect() {
    var value = address.value.toLowerCase();
    for (var i = 0; i < towns.length; i += 1) {
      if (value.indexOf(towns[i].toLowerCase()) !== -1) {
        town.value = towns[i];
        return;
      }
    }
    if (value.indexOf("lowndes") !== -1) {
      town.value = "Lowndes County";
    }
  }
  address.addEventListener("input", detect);
})();
