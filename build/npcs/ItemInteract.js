function ccs(text) {
    return text.replace(/&/g, '\u00A7');
}

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




function cancel(e, text) {
    if(text) {
        e.player.message(ccs(text));
    }
    if(e.isCancelable()) {
        e.setCanceled(true);
    }
    return false;
}
Date.prototype.addTime = function(addTime) {
    this.setTime(this.getTime() + addTime);
};

Date.prototype.hasPassed = function(passDate) {
    return (this.getTime() >= passDate.getTime());
};

var msTable = {
    //Reallife time
    'y': 31556926000, //365.25 days for taking leap years into account
    'mon': 2629743830, //
    'w': 604800000,
    'd': 86400000,
    'h': 3600000,
    'min': 60000,
    's': 1000,
    'ms': 1,
};

//Converts TimeString to number
function getStringTime(timeString) {
    //0y4mon3d 6h 8min3s 800ms
    var reg = /([\d]+)([a-zA-Z]+)/g;
    var _m = timeString.match(reg);
    var newTime = NaN;
    var _tk = Object.keys(msTable);

    for (var m in _m) {
        var fm = _m[m];
        var nm = parseInt(fm.replace(reg, '$1'))||0;
        var om = fm.replace(reg, '$2');
        if (nm != null) {
            if (isNaN(newTime)) { newTime = 0; }
            if (_tk.indexOf(om) != -1) {
                newTime += nm * (msTable[_tk[_tk.indexOf(om)]]);
            } else { newTime += nm; }
        }
    }

    return newTime;
}
//Converts number to TimeString
function getTimeString(stringTime, excludes) {
    excludes = excludes || [];
    var newTime = parseInt(stringTime);
    var newStr = '';
    for (var ms in msTable) {
        if (excludes.indexOf(ms) == -1) {
            var msnum = 0;
            while (newTime >= msTable[ms]) {
                msnum++;
                newTime -= msTable[ms];
            }
            if (msnum > 0) {
                newStr += msnum.toString() + ms;
            }
        }
    }


    return newStr;
}


function nbtToItem(w, nbt) {
    if(!nbt) {
        nbt = '{id:"minecraft:air"}';
    }
	var nbtObject = API.stringToNbt(nbt);
	if(nbtObject.getString('id') == 'minecraft:air') {
		return w.createItem('minecraft:air', 0, 0);
	}
	return w.createItemFromNbt(nbtObject);
}

var API = Java.type('noppes.npcs.api.NpcAPI').Instance();
var INbt = Java.type('noppes.npcs.api.INbt');
var LogManager = Java.type('org.apache.logging.log4j.LogManager');
var Logger = LogManager.getLogger(typeof CONFIG_SERVER != typeof undefined ? CONFIG_SERVER.NAME : "");
var ForgeLoader = Java.type('net.minecraftforge.fml.common.Loader').instance();
var EntityType = Java.type('noppes.npcs.api.constants.EntityType');
var REGISTRY = Java.type('net.minecraftforge.fml.common.registry.ForgeRegistries');

var NbtTypes = {
    "Byte": 1,
    "Short": 2,
    "Integer": 3,
    "Long": 4,
    "Float": 5,
    "Double": 6,
    "ByteArray": 7,
    "String": 8,
    "List": 9,
    "Compound": 10,
    "IntegerArray": 11,
};

function getNbtType(num) {
    for(var n in NbtTypes) {
	var nbtType = NbtTypes[n];
        if(nbtType === num) { return n; }
    }
    return null;
}

function getMCModList() {
    var modlist = [];
    var loadmods = Java.type("net.minecraftforge.fml.common.Loader").instance().getModList();

    for(var mid in loadmods) {
	var lmod = loadmods[mid];
        modlist.push(lmod.getModId());
    }

    return modlist;
}

function hasMCMod(name) {
    return getMCModList().indexOf(name) > -1;
}


/* GUI Id Reserver. Auto Generated IDs */
id('gui_main');
id('lbl_UUID');
id('lbl_screen');
id('lbl_title');
id('lbl_required_items');
id('lbl_cooldown');
id('txt_cooldown');
id('lbl_hvr_mainhand');
id('lbl_hvr_offhand');
id('rect_item_mainhand');
id('rect_item_offhand');
id('btn_save');
id('btn_credits');
id('lbl_credits_title');
id('lbl_credits_desc');
id('lbl_credits_to');
id('btn_back');
/* End GUI Id Reserver */

function interact(e) {
    if(e.player.gamemode == 1 && e.player.isSneaking()) {
        initGui(e.npc,null,'admin',e.player);
        cancel(e);
    }
}

function initGui(npc, gui, screen, player, options) {
    if(!gui) {
        if(player.getCustomGui()) {
            player.closeGui();
        }
        gui  = API.createCustomGui(id('gui_main'), 256, 256, false);
    } else {
        var components = Java.from(gui.getComponents());
        for(var i in components) {
	var component = components[i];
            gui.removeComponent(component.getID());
        }
    }
    var data = npc.storeddata;
    var now = new Date().getTime();
    var texture = 'customnpcs:textures/gui/menubg.png';
    
    var lbl_UUID = gui.addLabel(id('lbl_UUID'), npc.UUID, -500, 200, 128, 16);
    var lbl_screen = gui.addLabel(id('lbl_screen'), screen, -500, 200, 128, 16);

    //====GUI VARS
    var reqItems = JSON.parse(data.get('REQUIRED_ITEMS') || '{}');
    var cooldown = parseInt(data.get('COOLDOWN'))||1;

    //====GUI HEADER
    switch(screen) {
        case 'admin':
            gui.setBackgroundTexture(texture);
            gui.setSize(256,218);

            gui.addLabel(id('lbl_title'), ccs('&rItem Requirements for interact'), 6, 3, 256, 16);
            gui.addLabel(id('lbl_required_items'), ccs('&4&lRequired Items:'), 68, 32, 256, 16);

            var lbl_cooldown = gui.addLabel(id('lbl_cooldown'), ccs('&4&lCooldown: &c[?]\n'), 6, 85, 80, 30);
            lbl_cooldown.setHoverText(ccs('&6Set the cooldown the player has to wait to interact again.\n\nAllowed time units are: &a'+Object.keys(msTable).join(', ')+'\n\n&6Example: &e6h30min10s'));

            var txt_cooldown = gui.addTextField(id('txt_cooldown'), 6, 102, 65, 14);
            txt_cooldown.setText(getTimeString(cooldown));

            
            var lbl_hvr_mainhand = gui.addLabel(id('lbl_hvr_mainhand'), '', 68, 48, 18, 18);
            gui.addItemSlot(29, 23, nbtToItem(npc.world,reqItems[0]||null));
            lbl_hvr_mainhand.setHoverText(ccs('&6&nMainhand&r&6 item requirement slot\n\nThe item in this slot has to be specifically in the mainhand in order to be able to interact.\n\nAir is ignored'))
            var lbl_hvr_offhand = gui.addLabel(id('lbl_hvr_offhand'), '', 68, 66, 18, 18);
            gui.addItemSlot(29, 41, nbtToItem(npc.world,reqItems[1]||null));
            lbl_hvr_offhand.setHoverText(ccs('&6&nOffhand&r&6 item requirement slot\n\nThe item in this slot has to be specifically in the offhand in order to be able to interact.\n\nAir is ignored'))
            //mainhand item slot
            gui.addTexturedRect(id('rect_item_mainhand'), 'customnpcs:textures/gui/slot.png', 68,48,18,18, 0, 18);
            gui.addTexturedRect(id('rect_item_offhand'), 'customnpcs:textures/gui/slot.png', 68,66,18,18, 0, 54);

            
            var rect_items = gui.addTexturedRect(id('rect_items'), 'minecraft:textures/gui/container/generic_54.png', 86, 48, 162, 72, 7, 17);
            
            for(var i = 0; i < 36; i++) {
                gui.addItemSlot(47+(i % 9)*18, 23+Math.floor(i/9)*18, nbtToItem(npc.world,reqItems[2 + i]||null));
            }
            
            var rect_playerInv = gui.addTexturedRect(id('rect_playerInv'), 'minecraft:textures/gui/container/inventory.png', 118, 138, 164, 80, 6, 82);
            
            gui.showPlayerInventory(80, 114);

            gui.addButton(id('btn_save'), ccs('Save'), 6, 190, 50, 20);
            gui.addButton(id('btn_credits'), ccs('Credits'), 60, 190, 50, 20);
            break;
        case 'credits':
            gui.setBackgroundTexture('customnpcs:textures/gui/smallbg.png');
            gui.setSize(176,220);

            break;
    }



    //====GUI BODY
    switch(screen) {
        case 'credits':
            gui.addLabel(id('lbl_credits_title'), ccs("&r&l[==]&r &aInteract Requirement &r&l[==]"), 6, 6, 200, 16);

            gui.addLabel(id('lbl_credits_desc'), ccs('&6A script to require the player to hold items for any interact action on NPC.'), 6, 32, 150, 16);
            gui.addLabel(id('lbl_credits_to'), ccs('&6This script is custom made for &c&l.Anxiogène.&6\n\nScripted by &c&lRunonstof'), 6, 90, 150, 16)
            gui.addButton(id('btn_back'), 'Back', 43, 190, 90 ,20);
            break;
    }

    if(player) {
        player.showCustomGui(gui);
        gui.update(player);
    }
}


function customGuiButton(e) {
    var npc = e.player.world.getEntity(e.gui.getComponent(id('lbl_UUID')).getText());
    if(!npc) {
        return false;
    }

    var data = npc.storeddata;

    var saveSettings = function() {
        data.put('COOLDOWN', getStringTime(e.gui.getComponent(id('txt_cooldown')).getText()||'1ms'));

    };

    switch(e.buttonId) {
        case id('btn_save'):
            saveSettings();
            e.player.closeGui();
            e.player.message(ccs('&aSaved changes to NPC.'));
            break;
        case id('btn_credits'):
            initGui(npc,null,'credits',e.player);
            break;
        case id('btn_back'):
            initGui(npc,null,'admin',e.player);
            break;
    }

}

function customGuiSlot(e) {
    var npc,screen;
    try {
        npc = e.player.world.getEntity(e.gui.getComponent(id('lbl_UUID')).getText());
        screen = e.gui.getComponent(id('lbl_screen')).getText();
    } catch(exc) {}
    if(!npc || !screen) {
        return false;
    }

    var data = npc.storeddata;
    var reqItems = JSON.parse(data.get('REQUIRED_ITEMS')||'{}');
    switch(screen) {
        case 'admin':
            if(e.stack.isEmpty()) {
                delete reqItems[e.slotId];
            } else {
                reqItems[e.slotId] = e.stack.getItemNbt().toJsonString();
            }

            data.put('REQUIRED_ITEMS', JSON.stringify(reqItems));
            break;
    }

}
