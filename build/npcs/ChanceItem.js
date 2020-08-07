

function cancel(e, text) {
    if(text) {
        e.player.message(ccs(text));
    }
    e.setCanceled(true);
    return false;
}

function interact(e) {
    if(e.player.gamemode == 1 && e.player.isSneaking()) {
        var role = e.npc.role;
        if(!role) {
            return cancel(e);
        }
        
        if(role.getType() != RoleType_TRADER) {
            return cancel(e);
        }
        initGui(e.npc,null,'chance_items',e.player);
        cancel(e);
    }
}

function initGui(npc, gui, screen, player, options) {
    if(!gui) {
        if(player.getCustomGui()) {
            player.closeGui();
        }
        gui  = API.createCustomGui(@id('gui_main')$, 256, 256, false);
    } else {
        var components = Java.from(gui.getComponents());
        for(var i in components) {
	var component = components[i];
            gui.removeComponent(component.getID());
        }
    }
    var data = npc.storeddata;
    var now = new Date().getTime();
    var texture = 'customnpcs:textures/gui/tradersetup.png';
    
    var lbl_UUID = gui.addLabel(@id('lbl_UUID')$, npc.UUID, -500, 200, 128, 16);
    var lbl_screen = gui.addLabel(@id('lbl_screen')$, screen, -500, 200, 128, 16);
    var chances = JSON.parse(data.get('CHANCES') || '{}');
    var chanceItems = JSON.parse(data.get('CHANCE_ITEMS') || '{}');
    //Gui HEADERS
    switch(screen) {
        case 'chance_items':
            gui.setBackgroundTexture(texture);
            gui.setSize(250,215);

            gui.addLabel(@id('lbl_title')$, ccs('&rTrader Chance Items &8(0%% - 100%%)'), 6, 3, 256, 16)
            break;
        case 'credits':
            gui.setBackgroundTexture('customnpcs:textures/gui/smallbg.png');
            gui.setSize(176,220);


            break;
    }
    

    var invSlotLinks = {};
    var s = 0;
    var xysMap = {};
    //GUI body
    switch(screen) {
        case 'chance_items':
            var maxRows = 5;
            for(var x = 0; x < 3; x++) {
                for(var y = 0; y < 5; y++) {
                    gui.addTexturedRect(@id('rect_chanceItem_'+x+'_'+y)$, 'customnpcs:textures/gui/slot.png', 16+x*72,18+y*24,18,18)
                    var slot = gui.addItemSlot(-20 + x * 72, -6 + y * 24, nbtToItem(player.world,chanceItems[s]||null)).setID(id('slot_'+x+'_'+y));
                    xysMap[s] = x+'_'+y;
                    s++;
                    var txt = gui.addTextField(@id('txt_chance_'+x+'_'+y)$, 40+x *72, 19+y*24, 40, 16);
                    txt.setText(parseFloat(chances[x+'_'+y]||0).toString());
                    gui.updateComponent(txt);

                    gui.addLabel(@id('lbl_percent_'+x+'_'+y)$, ccs('&7%%'), 70+x*72,20+y*24,16,16);
                }
            }
            data.put('XYSMAP',JSON.stringify(xysMap));
            gui.addButton(@id('btn_save')$,'Save',3,150,42,20)
            gui.addButton(@id('btn_credits')$,'Credits',3,172,42,20)
            gui.showPlayerInventory(11,112);
            break;
        case 'credits':
            gui.addLabel(@id('lbl_credits_title')$, ccs("&r&l[==]&r &aTrader Chance items &r&l[==]"), 6, 6, 200, 16);

            gui.addLabel(@id('lbl_credits_desc')$, ccs('&6A script to add items with chances to get when trading with this NPC.'), 6, 32, 150, 16);
            gui.addLabel(@id('lbl_credits_to')$, ccs('&6This script is custom made for &c&l.Anxiogène.&6\n\nScripted by &c&lRunonstof'), 6, 90, 150, 16)
            gui.addButton(@id('btn_back')$, 'Back', 43, 190, 90 ,20);
            break;
    }

    if(player) {
        gui.update(player);
        player.showCustomGui(gui);
    }
}


function customGuiButton(e) {
    var npc = e.player.world.getEntity(e.gui.getComponent(@id('lbl_UUID')$).getText());
    var data = npc.storeddata;

    switch(e.buttonId) {
        case @id('btn_save')$:
            var chances = JSON.parse(data.get('CHANCES') || '{}');
            for(var x = 0; x < 3; x++) {
                for(var y = 0; y < 5; y++) {
                    var text = e.gui.getComponent(@id('txt_chance_'+x+'_'+y)$).getText();
                    var num =  Math.max(Math.min((parseFloat(text || 0) || 0), 100), 0);
                    chances[x+'_'+y] = num.toString();
                }
            }

            data.put('CHANCES', JSON.stringify(chances));
            
            e.player.closeGui();
            e.player.message(ccs('&aSaved changes to NPC.'));
            break;
        case @id('btn_credits')$:
            initGui(npc,null,'credits',e.player);
            break;
        case @id('btn_back')$:
            initGui(npc,null,'chance_items',e.player);
            break;
    }

}
function customGuiSlot(e) {
    var lbl_screen = e.gui.getComponent(@id('lbl_screen')$);
    var lbl_UUID = e.gui.getComponent(@id('lbl_UUID')$);
    var npc;
    if(lbl_UUID) {
        npc = e.player.world.getEntity(lbl_UUID.getText());
    }
    if(lbl_screen && npc) {
        var data = npc.storeddata;
        var screen = lbl_screen.getText();

        switch(screen) {
            case 'chance_items':
                var chanceItems = JSON.parse(data.get('CHANCE_ITEMS') || '{}');

                if(e.stack.isEmpty()) {
                    delete chanceItems[e.slotId];
                } else {
                    chanceItems[e.slotId] = e.stack.getItemNbt().toJsonString();
                }
                data.put('CHANCE_ITEMS', JSON.stringify(chanceItems));
                break;  
        }
    }
}


function trade(e) {
    var data = e.npc.storeddata;
    var chanceItems = JSON.parse(data.get('CHANCE_ITEMS') || '{}');
    var chances = JSON.parse(data.get('CHANCES') || '{}');
    
    var xysMap = JSON.parse(data.get('XYSMAP')||{});
    for(var s in chanceItems) {
	var chanceItem = chanceItems[s];
        var xyIndex = xysMap[s];
        if(typeof xyIndex === 'undefined') {
            continue;
        }

        var chance = chances[xyIndex];
        var stack = nbtToItem(e.npc.world,chanceItem);
        if(stack.isEmpty()) {
            continue;
        }

        if(random_range(0,100) <= chance) {
            if(!e.player.giveItem(stack)) {
                e.player.dropItem(stack);
            }
        }
    }

}

function role(e) {
    yield npc_role_event;
}



function ccs(text) { return text.replace(/&/g, '\u00A7'); }