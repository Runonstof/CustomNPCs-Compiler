/**
 * JavaScript part of Compiler plugin: GuiIdReserver
 * make sure to yield _GUI_IDS also (its also a pre-defined block)
 */
var _GUI_IDS = {
    counter: 1,
    ids: {}
};
// var tempdata = API.getIWorld(0).getTempdata();
// if(tempdata.get('_GUI_IDS')) {
//     _GUI_IDS = tempdata.get('_GUI_IDS');
// } else {
//     tempdata.put('_GUI_IDS', _GUI_IDS);
// }

function id(name) {
    if(!name) { name = Math.random().toString(36).substring(7) + Math.random().toString(36).substring(7); }
    return _GUI_IDS.ids[name] || (_GUI_IDS.ids[name] = _GUI_IDS.counter++);
}
