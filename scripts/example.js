import importFile;

var npcFullName = npc => npc.name + ' ' + npc.display.title;

function interact(e) {
    e.npc.say(`My name is ${npcFullName(e.npc)}`);
}
