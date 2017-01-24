function setRemoveable(id, value) {
    var options = $$('select#remoaveable-' + id + ' option');
    if (value != 0) {
        options[1].selected = true;
        options[0].disabled = true;

    } else {
        options[0].disabled = false;
    }
}



