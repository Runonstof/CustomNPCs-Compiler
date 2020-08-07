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








var _GUI_IDS = {
    counter: 1,
    ids: {}
};
var tempdata = API.getIWorld(0).getTempdata();
if(tempdata.has('_GUI_IDS')) {
    _GUI_IDS = tempdata.get('_GUI_IDS');
} else {
    tempdata.put('_GUI_IDS', _GUI_IDS);
}

function id(name) {
    if(!name) { name = Math.random().toString(36).substring(7) + Math.random().toString(36).substring(7); }
    return _GUI_IDS.ids[name] || (_GUI_IDS.ids[name] = _GUI_IDS.counter++);
}

var paymentInvSize = {'width':7,'height':2};
var pagePaymentInvSize = {'width': 7, 'height':2};

var val_btn_true = 'Yes';
var val_btn_false = 'No';

var opt_btn_rentEnabled = [
    val_btn_true,
    val_btn_false
];

paymentInvSize.total = paymentInvSize.width * paymentInvSize.height;

function getTraderGroupData(w, group) {
    var groupData = {
        traders: [],
        maxPerPlayer: 1
    };

    Object.assign(groupData, JSON.parse(w.storeddata.get('runon_traderGroup_'+group)||'{}'));

    groupData.traders = groupData.traders.filter(function(trader) {
        return !!w.getEntity(trader);
    });

    return groupData;
}

function customGuiButton(e) {
    var npc = e.player.world.getEntity(e.gui.getComponent(id('lbl_UUID')).getText());
    var data = npc.storeddata;
    
    
    var rentedAt = data.get('RENTED_AT') || 0;
    var rentTime = getStringTime(getTimeString(data.get('RENT_TIME') || getStringTime('1w')));
    var rentCredit = data.get('RENT_CREDIT') || rentTime;
    var maxRentTimeString = getTimeString(data.get('MAX_RENT_TIME') || rentTime);
    var maxRentTime = getStringTime(maxRentTimeString);
    var owner = data.get('OWNER')||'';
    var group = data.get('GROUP')||'';

    var paymentInv = JSON.parse(data.get('PAYMENT_INVENTORY') || '{}');
    //getStringTime(e.gui.getComponent(id('txt_rentTime')).getText()||'1w')||getStringTime('1w')

    var savePayments = function() {
        var newRentTime = getStringTime(e.gui.getComponent(id('txt_rentTime')).getText()||'1w')||getStringTime('1w');
        data_put(data, {
            'RENT_ENABLED': (e.gui.getComponent(id('btn_rentEnabled')).getLabel() == val_btn_true).toString(),
            'RENT_TIME':  newRentTime,
            'MAX_RENT_TIME':  Math.max(getStringTime(e.gui.getComponent(id('txt_maxRentTime')).getText()||'1w')||getStringTime('1w'), newRentTime)
        });
    }

    var saveInventory = function() {
        data_put(data, {
            'COLS': Math.max(Math.min(parseInt(e.gui.getComponent(id('txt_inv_cols')).getText()||9)||0, 9), 1),
            'ROWS': Math.max(Math.min(parseInt(e.gui.getComponent(id('txt_inv_rows')).getText()||6)||0, 6), 1),
            'PAGES': Math.max(Math.min(parseInt(e.gui.getComponent(id('txt_inv_pages')).getText()||1)||1, 100), 1),
            'START_PAGES': Math.max(Math.min(parseInt(e.gui.getComponent(id('txt_inv_pages')).getText()||1)||1, 100), 1)
        });
    }

    var saveGroupSettings = function() {
        var newGroup = e.gui.getComponent(id('txt_group')).getText() || '';
        data.put('GROUP', newGroup);
        var worlddata = e.player.world.storeddata;

        //If there was an old group, and group gets changed too
        if(!!group && group !== newGroup) {
            //edit the old group, remove trader from it
            var oldGroupKey = 'runon_traderGroup_'+group;
            if(worlddata.has(oldGroupKey)) {
                var oldGroupData = JSON.parse(worlddata.get(oldGroupKey));
                oldGroupData.traders = (oldGroupData.traders||[]).filter(function(trader) {
                    return trader !== npc.UUID;
                });

                worlddata.put(oldGroupKey, JSON.stringify(oldGroupData));
            }
        }
        if(newGroup) {
        //save to new group
            var groupData = getTraderGroupData(e.player.world, newGroup);
            if(groupData.traders.indexOf(npc.UUID) == -1) {
                groupData.traders.push(npc.UUID);
            }
            if(group && group == newGroup) {
                groupData.maxPerPlayer = Math.max(Math.min(parseInt(e.gui.getComponent(id('txt_group_maxPerPlayer')).getText()||1)||1,100),1);
            }
            worlddata.put('runon_traderGroup_'+newGroup, JSON.stringify(groupData));
        }

    }


    switch(e.buttonId) {
        //OPEN MENU BUTTONS
        case id('btn_settings'):
            // e.gui.getComponent(id('lbl_screen')).setText('settings');
            initGui(npc,e.gui,'settings',e.player);
            break;
        case id('btn_payment'):
            initGui(npc,null,'payment',e.player);
            break;
        case id('btn_inv_settings'):
            initGui(npc,e.gui,'inv_settings',e.player);
            break;
        case id('btn_admin_inv'):
            initGui(npc,null,'admin_inv',e.player);
            break;
        case id('btn_own_inv'):
            initGui(npc,null,'owner_inv',e.player);
            break;
        case id('btn_lines'):
            initGui(npc,null,'lines',e.player);
            break;
        case id('btn_lines_rent'):
            initGui(npc,null,'line_settings',e.player,{line_settings:'rent'});
            break;
        case id('btn_group_settings'):
            initGui(npc,null,'group_settings',e.player);
            break;
        //SETTING BUTTONS
        case id('btn_rentEnabled'):
            var btn_rentEnabled = e.gui.getComponent(id('btn_rentEnabled'));
            var new_rentEnabled = switchValue(btn_rentEnabled.getLabel(), opt_btn_rentEnabled);
            var cc = btn_rentEnabled.setLabel(new_rentEnabled);
            
            savePayments();
            initGui(npc,null,'payment',e.player);
            break;
        case id('btn_rentCreditMinus'):
            var subTime = getStringTime(e.gui.getComponent(id('txt_rentCreditSet')).getText()||'1w')||getStringTime('1w');
            data.put('RENT_CREDIT', Math.max((data.get('RENT_CREDIT')|| rentTime) - subTime, 0))
            initGui(npc,e.gui,'settings',e.player,{
                settings: {
                    'rentCreditSet': getTimeString(subTime)
                }
            });
            break;
        case id('btn_rentCreditPlus'):
            var addTime = getStringTime(e.gui.getComponent(id('txt_rentCreditSet')).getText()||'1w')||getStringTime('1w');
            data.put('RENT_CREDIT', Math.max((data.get('RENT_CREDIT')|| rentTime) + addTime, 0))
            initGui(npc,e.gui,'settings',e.player,{
                settings: {
                    'rentCreditSet': getTimeString(addTime)
                }
            });
            break;
        //MENU SAVE BUTTONS
        case id('btn_save_settings'):
            //save settings
            setPlayerOwner(data,e.gui.getComponent(id('txt_owner')).getText()||'',rentTime);
            initGui(npc,e.gui,'admin',e.player);
            break;
        case id('btn_save_payment'):
            savePayments();
            initGui(npc,null,'admin',e.player);
            break;
        case id('btn_save_inv_settings'):
            saveInventory();
            initGui(npc,null,'admin',e.player);
            break;
        case id('btn_save_admin_inv'):
            initGui(npc,null,'admin',e.player);
            break;
        case id('btn_save_owner_inv'):
            initGui(npc,null,'owner',e.player);
            break;
        case id('btn_save_group'):
            saveGroupSettings();
            initGui(npc,null,'admin',e.player);
            break;
        case id('btn_cancel'):
            initGui(npc,null,'admin',e.player);
            break;
        case id('btn_own_rent'):
            initGui(npc,null,'rent',e.player);
            break;
        case id('btn_exit'):
            e.player.closeGui();
            break;

        //RENTING
        case id('btn_rent'):
            if(!!owner && owner == e.player.getName()) {
                var realRentCredit = data.get('RENT_CREDIT') || 0;
                var timeLeft = (rentedAt + rentCredit) - new Date().getTime();
                if(timeLeft + rentTime > maxRentTime) {
                    e.player.message(ccs('&cYou can only pre-pay up to&e '+maxRentTimeString+'.\n&eWait '+getTimeString(rentTime - (maxRentTime - timeLeft), ['ms'])+'&c before renting again.'));
                    initGui(npc,e.gui,'rent',e.player);
                    break;
                }
            }
            if(!!owner && owner != e.player.getName()) {
                e.player.message(ccs('&cYou are not the owner of this trader.'));
                break;
            }
            
            var hasAllItems = true;
            var pnbt = e.player.getEntityNbt();

            var operations = [];
            var stacks = [];
            var invMap = {};
            

            for(var i = 0; i < paymentInvSize.total; i++) {
                var stack = nbtToItem(e.player.world, paymentInv[i]||null);
                if(stack.isEmpty()) {
                    continue;
                }
                var nbt = stack.getItemNbt();
                nbt.remove('Count');
                var nbtJson = nbt.toJsonString();
                invMap[nbtJson] = (invMap[nbtJson]||0) + stack.getStackSize();
            }

            
            var mockInvMap = Object.assign({}, invMap);

            var playerInv = e.player.getInventory();
            var playerItems = Java.from(playerInv.getItems()).slice(0, 36);

            for(var checkNbt in invMap) {
	var itemCount = invMap[checkNbt];
                for(var i in playerItems) {
	var stack = playerItems[i];
                    if(stack.isEmpty()) {
                        continue;
                    }
                    var nbt = stack.getItemNbt();
                    nbt.remove('Count');
                    var nbtJson = nbt.toJsonString();

                    if(checkNbt == nbtJson) {
                        mockInvMap[nbtJson] -= stack.getStackSize();
                    }
                }
            }

            for(var i in mockInvMap) {
	var cnt = mockInvMap[i];
                if(cnt > 0) {
                    hasAllItems = false;
                    break;
                }
            }

            if(hasAllItems) {
                Object.keys(invMap).forEach(function(stackNbtString) {
                    var stackNbt = API.stringToNbt(stackNbtString);
                    stackNbt.setByte('Count', 1);
                    e.player.removeItem(e.player.world.createItemFromNbt(stackNbt), invMap[stackNbtString]);
                })
                if(owner != e.player.getName()) {
                    data.put('RENTED_AT', new Date().getTime());
                    data.put('RENT_CREDIT', 0);
                }
                setPlayerOwner(data, e.player.getName(), rentTime);
                e.player.message(ccs('&aYou successfully rented '+npc.getName()+'&r&a for &e'+getTimeString(rentTime)+'&a time! SHIFT+RCLICK to get started.'));
                if(e.player.getName() != owner) {
                    e.player.closeGui();
                } else {
                    initGui(npc,e.gui,'rent',e.player);
                }
            } else {
                e.player.message(ccs('&cYou don\'t have the required items to rent this trader.'));
            }
            break;
    }

}

function customGuiClose(e) {
    var lbl_UUID = e.gui.getComponent(id('lbl_UUID'));
    var npc;
    if(lbl_UUID) {
        npc = e.player.world.getEntity(lbl_UUID.getText());
    }
    var lbl_screen = e.gui.getComponent(id('lbl_screen'));
    var screen;
    if(lbl_screen) {
        screen = lbl_screen.getText();
    }
    if(npc) {
        if(e.player.getGamemode() == 1) {
            data.remove('ADMIN_EDIT');
        } else if(data.get('OWNER') == e.player.getName()) {
            data.remove('OWNER_EDIT');
        }
    }
    
}
function worldOut(str) {
    API.getIWorld(0).broadcast(ccs(str));
}
function setPlayerOwner(data, name, time) {
    var old_owner = data.get('OWNER')||'';

    if(name != old_owner) {
        data.put('RENTED_AT', new Date().getTime());
        data.put('RENT_CREDIT', time || 0);

        if(old_owner) {
            //TODO: save old owner's items
            /*
            [

            ]
            */
           var itemChestChunks = [];
        }
    } else {
        data.put('RENT_CREDIT', (data.get('RENT_CREDIT'))+time);
    }

    data.put('OWNER', name);
}

function savePlayerOwner(data, name) {
    
}

function hasOwnerSave(data, name) {
    return !!data.get('COLLECT_INV_'+name);
}

function customGuiSlot(e) {
    var lbl_screen = e.gui.getComponent(id('lbl_screen'));
    var lbl_UUID = e.gui.getComponent(id('lbl_UUID'));
    var npc;
    if(lbl_UUID) {
        npc = e.player.world.getEntity(lbl_UUID.getText());
    }
    if(lbl_screen && npc) {
        var data = npc.storeddata;
        var screen = lbl_screen.getText();
        switch(screen) {
            case 'payment':
                if(!validateEditLevel(npc,e.player,'admin')) {
                    e.player.closeGui();
                    return cancel(e, '&cYou don\'t have permission to this action');
                }
                var paymentInv = JSON.parse(data.get('PAYMENT_INVENTORY') || '{}');
                paymentInv[e.slotId] = e.stack.getItemNbt().toJsonString();
                data.put('PAYMENT_INVENTORY', JSON.stringify(paymentInv))
                break;
            case 'inv_settings':
                if(!validateEditLevel(npc,e.player,'admin')) {
                    e.player.closeGui();
                    return cancel(e, '&cYou don\'t have permission to this action');
                }
                var pagePaymentInv = JSON.parse(data.get('PAGE_PAYMENT_INVENTORY') || '{}');
                pagePaymentInv[e.slotId] = e.stack.getItemNbt().toJsonString();
                data.put('PAGE_PAYMENT_INVENTORY', JSON.stringify(pagePaymentInv))
                break;
            case 'owner_inv':
                if(!validateEditLevel(npc,e.player,'owner')) {
                    e.player.closeGui();
                    return cancel(e, '&cYou don\'t have permission to this action');
                }
                var inventory = JSON.parse(data.get('INVENTORY') || '{}');
                inventory[e.slotId] = e.stack.getItemNbt().toJsonString();
                data.put('INVENTORY', JSON.stringify(inventory));
                break;
            case 'admin_inv':
                if(!validateEditLevel(npc,e.player,'admin')) {
                    e.player.closeGui();
                    return cancel(e, '&cYou don\'t have permission to this action');
                }
                var inventory = JSON.parse(data.get('INVENTORY') || '{}');
                inventory[e.slotId] = e.stack.getItemNbt().toJsonString();
                data.put('INVENTORY', JSON.stringify(inventory));
                break;
        }
    }
}

function cancel(e, text) {
    if(text){
        e.player.message(ccs(text));
    }
    if(e.isCancelable()) {
        e.setCanceled(true);
    }
    return false;
}

function interact(e) {
    var data = e.npc.storeddata;
    if(e.player.isSneaking()) {
        var owner = data.get('OWNER');
        var rentEnabled = data.get('RENT_ENABLED') == 'true';
        var screen = null;
        if(e.player.gamemode == 1) {
            screen = 'admin';
        } else if(!owner && rentEnabled) {
            screen = 'rent';
        } else if(!owner && !rentEnabled) {
            return cancel(e, '&cThis trader is not for rent.');
        } else if(!!owner && owner != e.player.getName()) {
            return cancel(e, '&cYou are not the owner of this trader');
        }

        if(owner == e.player.getName() && e.player.getGamemode() != 1 && adminEdit) {
            return cancel(e, '&cThis trader is being edited by an admin.');
        }

        if(e.player.gamemode == 1) {
            data.put('ADMIN_EDIT', e.player.getName());
        } else if(owner == e.player.getName()) {
            data.put('OWNER_EDIT', e.player.getName());
        }

        initGui(e.npc,null,screen,e.player);
        // e.player.showCustomGui(gui);
        e.setCanceled(true);
    } else {
        var adminEdit = data.get('ADMIN_EDIT')||'';
        var ownerEdit = data.get('OWNER_EDIT')||'';
        if(adminEdit || ownerEdit) {
            return cancel(e, '&cThis trader is being edited by an owner or admin.');
        }
    }
}

function validateEditLevel(npc, player, type) {
if(typeof type === typeof undefined) { var type = 'owner'; }
    var data = npc.storeddata;
    var owner = data.get('OWNER')||'';
    var editModeOwner = data.get('OWNER_EDIT')||false;
    var editModeAdmin = data.get('ADMIN_EDIT')||false;

    if(editModeOwner&&!playerIsOnline(player.world,editModeOwner)) { editModeOwner = false; data.remove('OWNER_EDIT'); }
    if(editModeAdmin&&(!playerIsOnline(player.world,editModeAdmin) || (editModeAdmin == player.getName() && player.getGamemode() != 1))) { editModeAdmin = false; data.remove('ADMIN_EDIT'); }

    switch(type) {
        case 'trade':
            return !editModeOwner && !editModeAdmin;
            break;
        case 'owner':
            return owner == player.getName() && (editModeOwner == player.getName()) && (!editModeAdmin || editModeAdmin == player.getName());
            break;
        case 'admin':
            return player.getGamemode() == 1 && (editModeAdmin == player.getName());
            break;
    }

    return false;
}

function initGui(npc, gui, screen, player, options) {
    if(!gui) {
        player.closeGui();
        gui  = API.createCustomGui(id('gui_main'), 256, 256, false);
    } else {
        var components = Java.from(gui.getComponents());
        for(var i in components) {
	var component = components[i];
            gui.removeComponent(component.getID());
        }
    }
    var data = npc.storeddata;
    var wdata = player.world.storeddata;
    var now = new Date().getTime();
    var owner = data.get('OWNER');
    var cols = Math.max(Math.min(parseInt(data.get('COLS')||9)||0, 9), 1);
    var rows = Math.max(Math.min(parseInt(data.get('ROWS')||6)||0, 6), 1);
    var pages = Math.max(Math.min(parseInt(data.get('PAGES')||1)||1, 100), 1);
    var startPages = Math.max(Math.min(parseInt(data.get('START_PAGES')||1)||1, 100), 1);
    var pageCredit = Math.max(Math.min(parseInt(data.get('PAGE_CREDIT')||startPages)||startPages, 100), startPages);
    var rentTimeString = getTimeString(data.get('RENT_TIME') || getStringTime('1w'));
    var rentTime = getStringTime(rentTimeString);
    var maxRentTimeString = getTimeString(data.get('MAX_RENT_TIME') || rentTime);
    var maxRentTime = getStringTime(maxRentTimeString);
    var rentEnabled = data.get('RENT_ENABLED') == 'true';
    var rentedAt = data.get('RENTED_AT') || 0;
    var rentCredit = now >= rentedAt + rentTime ? data.get('RENT_CREDIT') || 0 : data.get('RENT_CREDIT') || rentTime;
    var inventory = JSON.parse(data.get('INVENTORY') || '{}');
    var paymentInv = JSON.parse(data.get('PAYMENT_INVENTORY') || '{}');
    var pagePaymentInv = JSON.parse(data.get('PAGE_PAYMENT_INVENTORY') || '{}');
    var group = data.get('GROUP') || '';

    screen = screen || 'owner';
    var texture = 'minecraft:textures/gui/demo_background.png';
    // gui.setScriptHandler(npc);
    var lbl_UUID = gui.addLabel(id('lbl_UUID'), npc.UUID, -500, 200, 128, 16);
    var lbl_screen = gui.addLabel(id('lbl_screen'), screen, -500, 200, 128, 16);

    var screenPerms = {
        'admin': [
            'admin', 'payment', 'inv_settings', 'settings',
            'admin_inv', 'lines', 'line_settings', 'group_settings'
        ],
        'owner': [
            'owner', 'owner_inv'
        ]
    };

    for(var permType in screenPerms) {
	var screenPerm = screenPerms[permType];
        if(screenPerm.indexOf(screen) > -1) {
            // player.message(permType + ': '+screenPerm);
            if(!validateEditLevel(npc,player,permType)) {
                // player.message(screen + ' - ' + permType);
                player.message(ccs('&cYou don\'t have permission to this action...'));
                player.closeGui();
                return false;
            }
        }
    }

    //GUI HEADER
    switch(screen) {
        case 'admin':
        case 'owner':
        case 'payment':
        case 'inv_settings':
        case 'settings':
        case 'group_settings':
        case 'lines':
        case 'line_settings':
        case 'rent':
            gui.setBackgroundTexture(texture);
            gui.setSize(250,200);

            var titleSuffix = '';
            if(player&&player.getGamemode() == 1) {
                titleSuffix = ' &8Admin View (Gamemode 1)';
            } else if(screen == 'rent') {
                titleSuffix = ' | &aHire new trader | Payment';
            }
         
            gui.addLabel(id('lbl_title'), ccs('Player Trader' + titleSuffix), 6, 3, 256, 16);        
            // var lbl_credits = gui.addLabel(id('lbl_credits'), ccs('&oBy Runonstof &6v1.0.1'), 6, 150, 128, 16);
            gui.addLabel(id(), ccs('&l_________________________________'), 6, 10, 256, 16);
            
            break;
        case 'admin_inv':
        case 'owner_inv':
        case 'inv':
            gui.setBackgroundTexture(texture);
            gui.setSize(250,240);
            break;
    }

    // gui.addButton(id('btn_test'), 'test', 0, 0);
    switch(screen) {
        case 'admin':
            gui.addLabel(id('lbl_owner'), ccs('&4&lOwner: &r'+(owner ? '&e'+owner : '&cNo Owner')), 6, 26, 128, 16);
            gui.addLabel(id('lbl_invsize'), ccs('&4&lInventory:\n&r'+cols+'x'+rows+' ('+(cols*rows)+' slots)'), 6, 42, 256, 16);
            gui.addButton(id('btn_settings'), "Owner Settings", 154, 24, 90, 20);
            gui.addButton(id('btn_payment'), "Payment Settings", 154, 46, 90, 20);
            gui.addButton(id('btn_inv_settings'), "Inv Settings", 154, 68, 90, 20);
            gui.addButton(id('btn_admin_inv'), "Inventory", 154, 90, 90, 20);
            gui.addButton(id('btn_group_settings'), "Group Settings", 154, 112, 90, 20);
            // gui.addButton(id('btn_lines'), "Lines", 154, 134, 90, 20);

            gui.addButton(id('btn_exit'), "Exit", 6, 134, 70, 20);
            // button_one.setSize(64, 32);
            // button_two.setSize(64, 32);
            break;
        case 'settings':
            gui.addLabel(id('lbl_owner'), ccs('&4&lOwner:'), 6, 26, 128, 16);
            var txt_owner = gui.addTextField(id('txt_owner'), 50, 26, 110, 15);
            txt_owner.setText(owner||'');

            
            if(owner) {
                if(rentedAt > 0) {
                    var timeAgo = getTimeString(new Date().getTime() - rentedAt, ['ms']);
                    gui.addLabel(id('lbl_rentedAt'), ccs('&4&lRent Start:        &e'+(timeAgo||'1s')+' ago'), 6, 48, 196, 15);
                }
                var timeLeft = (rentedAt + rentCredit) - new Date().getTime()
    
                if(timeLeft > 0) {
                    gui.addLabel(id('lbl_rentLeft'), ccs('&4&lRent Time Left:    &e'+getTimeString(timeLeft, ['ms'])+' left'), 6, 70, 196, 12);
                }
                var showRentCredit = getTimeString(rentCredit, ['ms']);
                gui.addLabel(id('lbl_rentCredit'), ccs('&4&lTotal rented:      '+(timeLeft > 0 ? '&e'+showRentCredit : '&c'+showRentCredit+' &c&o(Negative rent time left)')), 6, 90, 196, 12);

                gui.addButton(id('btn_rentCreditMinus'), '-', 86, 130, 20, 20);
                var txt_rentCreditSet = gui.addTextField(id('txt_rentCreditSet'), 110, 130, 60, 20);
                gui.addButton(id('btn_rentCreditPlus'), '+', 172, 130, 20, 20);
                txt_rentCreditSet.setText(getObjectProp(options||{},'settings.rentCreditSet')||rentTimeString);
            } else {
                gui.addLabel(id('btn_ownerRentInfo'), ccs(rentEnabled ? 'Rent is enabled and there is no owner.\nAny player coming across can rent this trader.' : 'Rent is disabled. No player can rent this trader.\nIf you want to enable, goto payment settings to enable it.'), 6, 64, 230, 12);
            }



            gui.addButton(id('btn_save_settings'), "Save", 6, 130, 45, 20);
            // gui.addButton(id('btn_cancel'), "Cancel", 55, 130, 45, 20);
            
            break;
        case 'payment':
            // gui.setSize(350,200);
            var rect_playerInv = gui.addTexturedRect(id('rect_playerInv'), 'minecraft:textures/gui/container/inventory.png', 75, 130, 164, 80, 6, 82);
            
            gui.showPlayerInventory(40, 115);
            gui.addLabel(id('lbl_init'),  'init', -500, 0, 100, 16)
        
            var lbl_rentEnabled = gui.addLabel(id('lbl_rentEnabled'), ccs('&4&lRent Enabled:\n&c[?]'), 6, 30, 100, 16);
            lbl_rentEnabled.setHoverText(ccs('&cSet if player can hire this trader with settings below'));
            var btn_rentEnabled = gui.addButton(id('btn_rentEnabled'), rentEnabled ? val_btn_true : val_btn_false, 98, 26, 45, 20);

            var lbl_rentTime = gui.addLabel(id('lbl_rentTime'), ccs('&4&lRent Time: &c[?]'), 6, 50, 130, 16);
            lbl_rentTime.setHoverText(ccs('&cThe amount of time to add per payment\nAllowed time units are:\n&a&o'+Object.keys(msTable).join(', ')+'&r\n\n&5Example:\n&d1mon5d20min'));
            var txt_rentTime = gui.addTextField(id('txt_rentTime'), 98, 50, 100, 15);
            txt_rentTime.setText(rentTimeString||'');

            var lbl_maxRentTime = gui.addLabel(id('lbl_maxRentTime'), ccs('&4&lMax Prepay: &c[?]'), 6, 66, 100, 24)
            lbl_maxRentTime.setHoverText(ccs('&cSet the max rent time the owner can &c&npre-pay&c, owner can still have npc forever\n&cThis is to prevent billionaires buying years up front.'));
            var txt_maxRentTime = gui.addTextField(id('txt_maxRentTime'), 98, 70, 100, 15);
            txt_maxRentTime.setText(maxRentTimeString||'');

            var lbl_paymentInv = gui.addLabel(id('lbl_paymentInv'), ccs('&4&lPayment Items:\n&c[?]'), 6, 90, 100, 24);
            lbl_paymentInv.setHoverText(ccs('&cSet the items needed to pay.\nThis will add <RentTime> to owner\'s rentcredit.\nOwner can pre-pay up to <MaxRentCredit>\n\nLeave empty for free rent, if <RentEnabled> is set to Yes'));
            var rect_paymentInv = gui.addTexturedRect(id('rect_paymentInv'), 'minecraft:textures/gui/container/shulker_box.png', 98, 90, paymentInvSize.width*18, paymentInvSize.height* 18, 7,17)


            if(Java.from(gui.getSlots()).length == 0) {
                for(var i = 0; i < paymentInvSize.height; i++) {
                    // npc.say('hi: '+i)
                    for(var j = 0; j < paymentInvSize.width; j++) {
                        var invIndex = (i*paymentInvSize.width) + j;
                        var stack = nbtToItem(player.world, paymentInv[invIndex]||null);
                        gui.addItemSlot(62+(j*18), 74+(i*18), stack).setID(id('slot_paymentItem_'+invIndex));
                    }
                }
            }

            // gui.addButton(id('btn_payment_items'), ccs('Payment Items'), 6, 70, 90, 20);

            gui.addButton(id('btn_save_payment'), "Save", 6, 130, 45, 20);
            // gui.addButton(id('btn_cancel'), "Cancel", 55, 130, 45, 20);
            break;
        case 'inv_settings':
            var lbl_inv_cols = gui.addLabel(id('lbl_inv_cols'), ccs('&4&lColumns:'), 6, 26, 128, 16);
            lbl_inv_cols.setHoverText(ccs('&cThe width of the trader\'s inventory, per page. (1-9)'));
            var txt_inv_cols = gui.addTextField(id('txt_inv_cols'), 72, 26, 30, 15);
            txt_inv_cols.setText(cols||'9');
            

            var lbl_inv_rows = gui.addLabel(id('lbl_inv_rows'), ccs('&4&lRows:'), 6, 46, 128, 16);
            lbl_inv_rows.setHoverText(ccs('&cThe height of the trader\'s inventory, per page. (1-6)'));
            var txt_inv_rows = gui.addTextField(id('txt_inv_rows'), 72, 46, 30, 15);
            txt_inv_rows.setText(rows||'5');
            
            var lbl_inv_pages = gui.addLabel(id('lbl_inv_pages'), ccs('&4&lMax Pages:'), 6, 66, 128, 16);
            lbl_inv_pages.setHoverText(ccs('&cThe &nmaximum&r&c amount of pages the owner can upgrade too.'));
            var txt_inv_pages = gui.addTextField(id('txt_inv_pages'), 72, 66, 30, 15);
            txt_inv_pages.setText(pages||'1');
            
            var lbl_inv_start_pages = gui.addLabel(id('lbl_inv_start_pages'), ccs('&4&lStart Pages:'), 110, 26, 128, 16);
            lbl_inv_start_pages.setHoverText(ccs('&cThe amount of pages a new owner starts on.'))
            var txt_inv_start_pages = gui.addTextField(id('txt_inv_start_pages'), 190, 26, 30, 15);
            txt_inv_start_pages.setText(startPages||'1');
            
            var lbl_inv_page_payment = gui.addLabel(id('lbl_inv_page_payment'), ccs('&4&lPage Upgrade Items:'), 110, 64, 128, 16);
            lbl_inv_page_payment.setHoverText(ccs('&cThe items to pay to get an extra inventory page.'));
            gui.addTexturedRect(id('rect_pagePaymentInv'), 'minecraft:textures/gui/container/shulker_box.png', 110, 80, pagePaymentInvSize.width*18, pagePaymentInvSize.height*18, 7, 17);
            //          var rect_paymentInv = gui.addTexturedRect(id('rect_paymentInv'), 'minecraft:textures/gui/container/shulker_box.png', 98, 90, paymentInvSize.width*18, paymentInvSize.height* 18, 7,17)



            if(Java.from(gui.getSlots()).length == 0) {
                for(var i = 0; i < pagePaymentInvSize.height; i++) {
                    // npc.say('hi: '+i)
                    for(var j = 0; j < pagePaymentInvSize.width; j++) {
                        var invIndex = (i*pagePaymentInvSize.width) + j;
                        var stack = nbtToItem(player.world, pagePaymentInv[invIndex]||null);
                        gui.addItemSlot(74+(j*18), 64+(i*18), stack).setID(id('slot_paymentItem_'+invIndex));
                    }
                }
            }

            var rect_playerInv = gui.addTexturedRect(id('rect_playerInv'), 'minecraft:textures/gui/container/inventory.png', 75, 130, 164, 80, 6, 82);
            
            gui.showPlayerInventory(40, 115);
           

            // gui.addLabel(id('lbl_inv_size_warn'), ccs('&rWARNING: Setting the inv size lower than before can remove items from inventory!'), 6, 102, 200, 15);
            gui.addButton(id('btn_save_inv_settings'), "Save", 6, 130, 45, 20);
        
            break;
        case 'owner_inv':
            var id_inv_name = 'owner';
        case 'admin_inv':
            var id_inv_name = id_inv_name || 'admin';
            var rect_adminInv = gui.addTexturedRect(id('rect_'+id_inv_name+'Inv'), 'minecraft:textures/gui/container/generic_54.png', 123 - (cols*9), 57 - (rows*9), cols*18, rows*18, 7,17);
            gui.addButton(id('btn_save_'+id_inv_name+'_inv'), id_inv_name == 'owner' ? "Back" : "Save", 5, 90, 34, 20);
            var rect_playerInv = gui.addTexturedRect(id('rect_playerInv'), 'minecraft:textures/gui/container/inventory.png', 41, 115, 164, 80, 6, 82);
            if(Java.from(gui.getSlots()).length == 0) {
                for(var i = 0; i < rows; i++) {
                    // npc.say('hi: '+i)
                    for(var j = 0; j < cols; j++) {
                        var invIndex = (i*cols) + j;
                        var stack = nbtToItem(player.world, inventory[invIndex]||null);
                        gui.addItemSlot(6+(j * 18) +((9-cols) * 9)  , -33 + (i *18) + ((6-rows) * 9) , stack).setID(id('slot_'+id_inv_name+'Item_'+invIndex));
                    }
                }
            }
            gui.showPlayerInventory(6, 80);
            break;
        case 'rent':
            var requirementText = [];
            for(var i = 0; i < paymentInvSize.height; i++) {
                // npc.say('hi: '+i)
                for(var j = 0; j < paymentInvSize.width; j++) {
                    var invIndex = (i*paymentInvSize.width) + j;
                    var stack = nbtToItem(player.world, paymentInv[invIndex]||null);
                    if(stack.isEmpty()) {
                        continue;
                    }
                    requirementText.push('\u00A7r'+stack.getStackSize()+'x '+stack.getDisplayName()+'\u00A7r');
                }
            }
            var c = -1;
            for(var i = 0; i < requirementText.length; i++) {
                //create label
                if(i != 0 && (i % 7 == 0 || i == requirementText.length-1) || requirementText.length == 1) {
                    c++;
                    gui.addLabel(id('lbl_requirements_'+i), requirementText.slice(c*7, c*7+7).join('\n'), 6+(c*105), 42, 100, 100);
                }
            }
            var lbl_rentInfo = gui.addLabel(id('lbl_rentInfo'), ccs("&cClick 'Rent' for &e"+getTimeString(rentTime, ['ms'])+" &crenting time"), 25, 163, 200, 20)
            if(owner == player.getName()) {
                var timeLeft = (rentedAt + rentCredit) - new Date().getTime()
    
                if(timeLeft > 0) {
                    gui.addLabel(id('lbl_own_rentLeft'), ccs('&4&lRent Time Left:    &e'+getTimeString(timeLeft, ['ms'])+' left'), 6, 177, 256, 12);
                }
            }
            
            var btn_rent = gui.addButton(id('btn_rent'), "Start renting", 60, 190, 120, 20);
            gui.addButton(id('btn_exit'), "Exit", 200, 190, 40, 20);
            break;
        case 'owner':
            gui.addLabel(id('lbl_own_invsize'), ccs('&4&lInventory:\n&r'+cols+'x'+rows+' ('+(cols*rows)+' slots)'), 6, 64, 256, 16);
            var timeLeft = (rentedAt + rentCredit) - new Date().getTime()
    
            if(timeLeft > 0) {
                gui.addLabel(id('lbl_own_rentLeft'), ccs('&4&lRent Time Left:    &e'+getTimeString(timeLeft, ['ms'])+' left'), 6, 36, 196, 12);
            }

            gui.addButton(id('btn_own_inv'), "Inventory", 154, 90, 90, 20);
            gui.addButton(id('btn_own_rent'), "Rent More", 154, 112, 90, 20);
            break;
        case 'market':

        break;
        case 'group_settings':
            gui.addLabel(id('lbl_group_info'), ccs('&rAny settings you change here &ogets also changed in NPC\'s within the same group.&r'), 6, 28, 220, 16);
            gui.addLabel(id('lbl_group'), ccs('&4&lGroup:'), 6, 49, 100, 16);
            var txt_group = gui.addTextField(id('txt_group'), 105, 50, 90, 14);
            txt_group.setText(group);
            
            if(group) {
                gui.addLabel(id('lbl_group_maxPerPlayer'), ccs('&4&lMax Per Player: &c[?]'), 6, 76, 100, 16);
                var groupData = getTraderGroupData(player.world, group);
                var txt_group_maxPerPlayer = gui.addTextField(id('txt_group_maxPerPlayer'), 105, 72, 40, 14);
                txt_group_maxPerPlayer.setText(parseInt(groupData.maxPerPlayer||1));

            } else {
                gui.addLabel(id('lbl_newGroup_info'), ccs('&cSet a new or existing group. for special settings across traders from same group.'), 6, 74, 220, 16);
            }

            gui.addButton(id('btn_save_group'), "Save", 6, 140, 90, 20);
            break;
        case 'lines':
            gui.addButton(id('btn_lines_rent'), "Rent Lines", 48, 28, 150, 20);
            gui.addButton(id('btn_lines_rented'), "Rented Lines", 48, 50, 150, 20);
            gui.addButton(id('btn_lines_rented_more'), "Rented More Lines", 48, 72, 150, 20);
            gui.addButton(id('btn_lines_owner'), "Owner Lines", 48, 94, 150, 20);
            
            break;
        case 'line_settings':
            var line_settings = options.line_settings || '';
            switch(line_settings) {
                case 'rent':
                    gui.addLabel(id('lbl_line_settings_rent'), ccs('Lines/Commands for when new potential owner opens the rent menu.'), 8, 28, 230, 16);
                    break;
                
            }

            if(line_settings) {
                for(var i = 0; i < 8; i++) {
                    var txt_line = gui.addTextField(id('txt_line_'+line_settings+'_'+i), 24, 24 + i*20, 90, 14);
                }
            }

            break;
    }
    if(player) {
        player.showCustomGui(gui);
        gui.update(player);
    }
    
}

function role(e) {
    yield npc_role_event;
}

function tick(e) {
    var data = e.npc.storeddata;
    var adminEdit = data.get('ADMIN_EDIT')||'';
    var ownerEdit = data.get('OWNER_EDIT')||'';
    var adminPlayer;
    if(adminEdit && (adminPlayer = e.npc.world.getPlayer(adminEdit||''))) {
        if(!adminPlayer.getCustomGui()) {
            data.remove('ADMIN_EDIT');
        } else {
            data.remove('OWNER_EDIT');
        }
    } else if(adminEdit) { data.remove('ADMIN_EDIT'); }
    var ownerPlayer;
    if(ownerEdit && (ownerPlayer = e.npc.world.getPlayer(ownerEdit||''))) {
        if(!ownerPlayer.getCustomGui()) {
            data.remove('OWNER_EDIT');
        }
    } else if(ownerEdit) { data.remove('OWNER_EDIT'); }
}

function switchValue(val, list) {
    return list[(list.indexOf(val) + 1) % list.length];
}


function ccs(text) { return text.replace(/&/g, '\u00A7'); }