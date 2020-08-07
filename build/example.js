"use strict";

function testFunction() {//test function
}

var npcFullName = function npcFullName(npc) {
  return npc.name + ' ' + npc.display.title;
};

function interact(e) {
  e.npc.say("My name is ".concat(npcFullName(e.npc)));
}