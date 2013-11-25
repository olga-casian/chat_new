// Main client class that handles XMPP events;
// Uses strophe.js library for XMPP support 
var Client = {
	// the Strophe XMPP connection object
	connection: null,
	// contains contact roster in bare form;
	// as new contact joins the chat he is added to roster too
	roster: new Array(),
	// list of those who sent "subscribe" presence; element is deleted 
	// from this list as soon as "subscribed" presence is received; then the contact is added to roster
	subscribe: new Array(),
	// list of those who sent "subscribed" presence; element is deleted 
	// from this list as soon as "subscribe" presence is received; then the contact is added to roster
	subscribed: new Array(),
	// current full jid with resource
	my_full_jid: new String(),
	// timestamp of last sent presence with show equal to full jid
	last_presence_sent: '',
	// MUC jids of groups where the user is member
	mucs: new Array(),
	
	// 		XMPP EVENT HANDLERS
	on_roster: function (iq) {
		jQuery(iq).find('item').each(function () {
			var jid = jQuery(this).attr('jid');
			if (jid != '[object object]') {
				Client.roster.push(jid);
				Client.roster[jid] = 'offline';
			}
		});
		console.log('roster: ' + Client.roster);
		
		// send initial presence
		Client.connection.send($pres());
		
		// everyone in roster except me is shown as offline till presence is received
		jQuery('.friends_column_wrapper').each(function () {
			if (jQuery(this)[0].className.search('me') == -1) {
				Client.replace_contact(jQuery(this)[0], false);
			}
		});
	},
	
	on_presence_subscribe: function (presence) {
		var from = jQuery(presence).attr('from');
		var from_bare = Strophe.getBareJidFromJid(from);
		
		// do nothing if received data is not from course members
		if (Client.check_membership(from_bare) == false) {
			console.log("presence subscribe from non-member: " + from_bare);
			return;
		}
		
		if (jQuery.inArray(from_bare, Client.subscribe) == -1 && jQuery.inArray(from_bare, Client.subscribed) > -1) {
			// i initiated, finishing subscription
			// auto approve
			Client.connection.send($pres({
				to: from,
				"type": "subscribed"}));
			Client.subscribe.push(from_bare);				
			
		} else if (jQuery.inArray(from_bare, Client.subscribe) == -1 && jQuery.inArray(from_bare, Client.subscribed) == -1) {
			// he initiated, starting
			// auto approve
			Client.connection.send($pres({
				to: from,
				"type": "subscribed"}));
			Client.connection.send($pres({
				to: from,
				"type": "subscribe"}));
			Client.subscribe.push(from_bare);
		}
			
		if (jQuery.inArray(from_bare, Client.subscribe) > -1 && jQuery.inArray(from_bare, Client.subscribed) > -1) {
			// remove
			removeItem = from_bare;
			Client.subscribe = jQuery.grep(Client.subscribe, function(value) {
				return value != removeItem;
			});
			Client.subscribed = jQuery.grep(Client.subscribed, function(value) {
				return value != removeItem;
			});
			
			// added to roster
			Client.roster.push(from_bare);
			Client.roster[from_bare] = 'online';
			Client.get_new_contact_data(from_bare);
			Client.connection.send($pres().c('show').t(Client.my_full_jid));
			Client.last_presence_sent = +new Date;
		}
			
		return true;
	},
	
	on_presence_subscribed: function (presence) {
		var from = jQuery(presence).attr('from');
		var from_bare = Strophe.getBareJidFromJid(from);
		
		// do nothing if received data is not from course members
		if (Client.check_membership(from_bare) == false) {
			console.log("presence subscribed from non-member: " + from_bare);
			return;
		}
		
		if (jQuery.inArray(from_bare, Client.subscribe) > -1 && jQuery.inArray(from_bare, Client.subscribed) == -1) {
			// he initiated, finishing subscription
			Client.connection.send($pres({
				to: from,
				"type": "subscribe"}));
			Client.subscribed.push(from_bare);
				
		} else if (jQuery.inArray(from_bare, Client.subscribe) == -1 && jQuery.inArray(from_bare, Client.subscribed) == -1) {
			// i initiated 
			Client.subscribed.push(from_bare);
		}
			
		if (jQuery.inArray(from_bare, Client.subscribe) > -1 && jQuery.inArray(from_bare, Client.subscribed) > -1) {
			// remove
			removeItem = from_bare;
			Client.subscribe = jQuery.grep(Client.subscribe, function(value) {
				return value != removeItem;
			});
			Client.subscribed = jQuery.grep(Client.subscribed, function(value) {
				return value != removeItem;
			});
			
			// added to roster
			Client.roster.push(from_bare);
			Client.roster[from_bare] = 'online';
			Client.get_new_contact_data(from_bare);
			Client.connection.send($pres().c('show').t(Client.my_full_jid));
			Client.last_presence_sent = +new Date;
		}
			
		return true;
	},
	
	on_presence: function (presence) {		
		var ptype = jQuery(presence).attr('type');
		var from = jQuery(presence).attr('from');
		var from_bare = Strophe.getBareJidFromJid(from);
		var from_bare_id = Client.jid_to_id(from_bare);
		var to_bare = Strophe.getBareJidFromJid(jQuery(presence).attr('to'));
		var nick = Strophe.getResourceFromJid(from);
		
		if (ptype == "subscribe") {
			Client.on_presence_subscribe(presence);
			return true;
			
		} else if (ptype == "subscribed") {
			Client.on_presence_subscribed(presence);
			return true;
		}

		// do nothing if received data is not from course members (for 'chat')
		if (Client.check_membership(from_bare) == false) {
			console.log("presence from non-member: " + from_bare);
			return true;
		}
		
		var muc_pres = false;
		if (jQuery(presence).find("item").attr('jid') != undefined) {
			var jid_bare_from_muc = Strophe.getBareJidFromJid(jQuery(presence).find("item").attr('jid'));
			var groupmates_jids = new Array();
			jQuery('.friends_column_wrapper').each(function(){
				if (jQuery(this).attr('id') != Strophe.getBareJidFromJid(Client.my_full_jid)) groupmates_jids.push(jQuery(this).attr('id'));
			});
			if (groupmates_jids.indexOf(jid_bare_from_muc) > -1) {
				muc_pres = true;
			}
		}

		var owner = false;
		if (Client.mucs[from_bare] != undefined) {
			if (Client.mucs[from_bare]['invites_to'].length > 0) {
				owner = true;
			}
		}

		if (muc_pres == true && owner == false) {
			// muc presence, i'm member
			if (jQuery(presence).attr('type') === 'error' && Client.mucs[from_bare]["joined"] == false) {
				if (jQuery(presence).find('text').text() != '') {
					alert("An error while entering multi-user chat room occured: " + jQuery(presence).find('text').text());
				}
				
			} else if (Client.mucs[from_bare]["joined"] == false) {
				// room join complete
				jQuery(document).trigger('room_joined', from_bare);
				
				// http://xmpp.org/extensions/xep-0045.html#modifymember
				// require member list
				var request_id = Client.connection.sendIQ(
			    	$iq({type: "get", to: from_bare})
				        .c("query", {xmlns: 'http://jabber.org/protocol/muc#admin'})
				           	.c("item", {affiliation: 'admin'})
			    );
			    
			    // handle room member list
				Client.connection.addHandler(Client.on_room_member_list_on_join, null, "iq", "result", request_id);
			}
			
		} else if (Client.mucs[from_bare] != undefined) {
			// muc presence, i'm owner
			if (jQuery(presence).attr('type') !== 'error' && Client.mucs[from_bare]["joined"] == false) {
				// check for status 110 to see if it's our own presence and we are the owner of muc
				if (jQuery(presence).find("status[code='110']").length > 0 && jQuery(presence).find("item[affiliation='owner']").length > 0) {
					var request_id = Client.connection.muc.configure(from_bare);
					// handle muc config form
					Client.connection.addHandler(Client.on_room_form, null, "iq", "result", request_id);
				}
				
				if (Client.mucs[from_bare]["joined"] == false) {
					// room join complete
					jQuery(document).trigger('room_joined', from_bare);			
					
					if (Client.mucs[from_bare]["invites_to"].length > 0) {
						// add self
						var jid_id = Client.jid_to_id(from_bare);
						var my_id = jQuery("div").filter(jQuery('#chat').find('div')[1]).attr('id');
						jQuery('#chat_' + jid_id + ' .muc_roster ul').append("<li class='muc_roster_me' style='background-color:white; border:2px solid #BBB;'><a href='profile.php?id=" + 
							my_id + "'>" + Client.mucs[from_bare]["nickname"] + "</a></li>");
						
						// add others
						var members = '';
						for (var i = 0; i < Client.mucs[from_bare]["invites_to"].length; i++) {
							// add to muc roster
							var id = jQuery("div").filter(document.getElementById(Client.mucs[from_bare]["invites_to"][i]["jid"])).find("table").attr("id");
							var list_item = "<li class='muclist_" + Client.mucs[from_bare]["invites_to"][i]["jid"] + "'><a href='profile.php?id=" + id + "'>" + 
								Client.mucs[from_bare]["invites_to"][i]["nick"] + "</a></li>";
							
							var inserted = false;
							jQuery('#chat_' + jid_id + ' .muc_roster li').each(function () {
								var cmp_name = jQuery(this).find('a')[0].textContent;
								if (Client.mucs[from_bare]["invites_to"][i]["nick"] < cmp_name) {
									jQuery(this).before(list_item);
									inserted = true;
									return false;
								}
							});
							if (!inserted) {
								// insert after last element of group
								jQuery('#chat_' + jid_id + ' .muc_roster ul').append(list_item);
							}							
							
							members += Client.mucs[from_bare]["invites_to"][i]["jid"] + '  ';
						}
						members += Strophe.getBareJidFromJid(Client.my_full_jid);
					
						// new DB entry for muc
						var dataString = 'roomname=' + from_bare + '&members=' + members;
						jQuery.ajax({
							type: "POST",
							url: "ATutor/mods/chat_new/ajax/new_muc.php",
							data: dataString,
							cache: false,
							success: function (returned) {
								// console.log("returned ON MUC CREATION: ",returned);
							},
							error: function (xhr, errorType, exception) {
							    console.log("error: " + exception);
							}		
						});
					}					
				}
			}
			
			if (jQuery(presence).attr('type') === 'error' && Client.mucs[from_bare]["joined"] == false) {
				alert("An error while entering '" + from_bare + "' multi-user chat room occured: " + jQuery(presence).find('text').text());
			}

			
			
		} else if (from.indexOf('@conference.talkr.im') == -1) {
			// contact presence
			if (ptype !== 'error' && from_bare != to_bare) {
				var contact_roster = document.getElementById(from_bare);
				if (jQuery(presence).find("show").text() == from) {
					jQuery("div").filter(document.getElementById(from_bare)).data('jid', from);
				}

				if (ptype === 'unavailable' && jQuery("div").filter(document.getElementById(from_bare)).data('jid') == from) {
					// ATutor chat user
					online = false;
				} else if (ptype === 'unavailable' && jQuery("div").filter(document.getElementById(from_bare)).data('jid') == undefined) {
					// other client user
					online = false;
				} else {
					var show = jQuery(presence).find("show").text();
					if (show === "" || show === "chat") {
						online = true;
					} else {
						online = true;
					}					
				}	
				
				if (jQuery(presence).find("item[affiliation='owner']").length == 0 &&
					jQuery(presence).find("item[affiliation='member']").length == 0 &&
					contact_roster != null) {
					
					Client.replace_contact(contact_roster, online);	
				}
				
				// change status in muc rosters
				jQuery("#subtabs").find(".conversations_table textarea").each(function () {
					var nick = '';
					var jid = jQuery(this).attr("id").slice(5, jQuery(this).attr("id").length);
					if (jQuery('#chat_' + Client.jid_to_id(jid)).length !== 0) {
						jQuery("#chat_" + Client.jid_to_id(jid)).find(".muc_roster li").each(function () {
							if (jQuery(this).attr('class').search('muclist_' + Strophe.getBareJidFromJid(from)) != -1) {
								var nick = jQuery(this).find('a').text();
								Client.muc_user_status_change(Strophe.getBareJidFromJid(from), nick, jid, online);
							}
						});
					}
				});
			}
			
			// resend presence to avoid wrong "unavailable" stanzas
			var now = +new Date;
			if (now - Client.last_presence_sent >= 30000) {
				Client.connection.send($pres().c('show').t(Client.my_full_jid));
				Client.last_presence_sent = +new Date;
			}			
		}
		
		return true;
	},
	
	on_room_form: function (iq) {
		var from = jQuery(iq).attr('from');
		
        var request_id = Client.connection.sendIQ(
        	$iq({type: "set", to: from})
	            .c("query", {xmlns: Strophe.NS.MUC_OWNER})
	            	.c("x", {xmlns: "jabber:x:data", type: "submit"})
	            		.c("field", {'var': "FORM_TYPE"})
	            			.c("value").t("http://jabber.org/protocol/muc#roomconfig").up().up()
	            			
	            		// Make room persistent
	            		.c("field", {'var': "muc#roomconfig_persistentroom"})
	            			.c("value").t("1").up().up()
	            			
	            		// Make participants list public
	            		.c("field", {'var': "public_list"})
	            			.c("value").t("0").up().up()
	            			
	            		// Make room members-only
	            		.c("field", {'var': "muc#roomconfig_membersonly"})
	            			.c("value").t("1").up().up()
	            			
	            		// Allow users to change the subject
	            		.c("field", {'var': "muc#roomconfig_changesubject"})
	            			.c("value").t("0").up().up()
	            			
	            		// Allow users to send private messages
	            		.c("field", {'var': "allow_private_messages"})
	            			.c("value").t("0").up().up()
	            			
	            		// Allow visitors to send private messages to
	            		.c("field", {'var': "allow_private_messages_from_visitors"})
	            			.c("value").t("nobody").up().up()
	            			
	            		// Allow visitors to send status text in presence updates
	            		.c("field", {'var': "muc#roomconfig_allowvisitorstatus"})
	            			.c("value").t("0").up().up()
	            			
	            		// Allow visitors to change nickname
	            		.c("field", {'var': "muc#roomconfig_allowvisitornickchange"})
	            			.c("value").t("0").up().up()
	            			
	            		// Allow visitors to send voice requests
	            		.c("field", {'var': "muc#roomconfig_allowvoicerequests"})
	            			.c("value").t("0")
        );

        // handle muc config result
		Client.connection.addHandler(Client.on_room_form_result, null, "iq", "result", request_id);
		
		return false;
	},
	
	on_room_form_result: function (iq) {
		var from = jQuery(iq).attr('from');
		
		// http://xmpp.org/extensions/xep-0045.html#modifymember
		// require member list
		var request_id = Client.connection.sendIQ(
        	$iq({type: "get", to: from})
	            .c("query", {xmlns: 'http://jabber.org/protocol/muc#admin'})
	            	.c("item", {affiliation: 'member'})
        );		
		
		// handle room member list
		Client.connection.addHandler(Client.on_room_member_list, null, "iq", "result", request_id);
		
		return false;
	},
	
	on_room_member_list: function (iq) {
		var from = jQuery(iq).attr('from');
		
		// http://xmpp.org/extensions/xep-0045.html#modifymember
		// modify member list		
		for (var i = 0; i < Client.mucs[from]["invites_to"].length; i++) {
			var jid = Client.mucs[from]["invites_to"][i]["jid"];
			var nick = Client.mucs[from]["invites_to"][i]["nick"];
			
			// add members
			var request_id = Client.connection.sendIQ(
	        	$iq({type: "set", to: from})
		            .c("query", {xmlns: 'http://jabber.org/protocol/muc#admin'})
		            	.c("item", {affiliation: 'admin', jid: jid, nick: nick})
	        );
	        
	        // send invites
	        Client.connection.send(
	        	$msg({from: Strophe.getBareJidFromJid(Client.my_full_jid),to: from})
	        		.c('x', {xmlns: "http://jabber.org/protocol/muc#user"}) 
            			.c("invite",{to:jid}) 
                    		.c("reason").t("Your nick: " + nick) 
            );
            
            // add to muc roster
            var id = jQuery("div").filter(document.getElementById(jid)).find("table").attr("id");
            var list_item = "<li class='muc_roster_me' style='background-color:white; border:2px solid #BBB;'><a href='profile.php?id=" + id + "'>" + nick + "</a></li>";
            var jid_id = Client.jid_to_id(jid);
			var inserted = false;
			jQuery('#chat_' + jid_id + ' .muc_roster li').each(function () {
				var cmp_name = jQuery(this).find('a')[0].textContent;
				if (nick < cmp_name) {
					jQuery(this).before(list_item);
					inserted = true;
					return false;
				}
			});
			if (!inserted) {
				// insert after last element of group
				jQuery('#chat_' + jid_id + ' .muc_roster ul').append(list_item);
			}
		}
        
		return false;
	},
	
	on_room_member_list_on_join: function (iq) {
		var nicks = new Array();
		var items = jQuery(iq).find('item');
		items.each(function(){			
			var nick = jQuery("div").filter(document.getElementById(jQuery(this).attr('jid'))).find('.friends_item_name').text();
			// avoid nick repetitions (as when assigning nicks)
			for (var i = 0; i < nicks.length; i++) {
				if (nicks[i] == nick) {
					var nr = nicks[i].match(/\([0-99]\)/);
					if (nr != null) {
						nr = parseInt(nr[0].slice(0, nr[0].length-1).slice(1, nr[0].length))+1;
						nick = nick.slice(0, nick.length -3) + "(" + nr + ")";
						continue;
					}
					nick = nick + "(1)";
					continue;
				} 				
			}
			nicks.push(nick);	
		});
		
	},
	
	on_muc_invite: function (message) {
		var from_room = jQuery(message).attr('from');
		var invite = jQuery(message).find('invite');

		if (invite.length > 0) {
			var from_bare = Strophe.getBareJidFromJid(invite.attr('from'));
			// do nothing if received data is not from course members
			if (Client.check_membership(from_bare) == false) {
				console.log("message from non-member: " + from_bare);
				return true;
			}
			
			var reason = jQuery(message).find('reason').text();
			var nick = reason.slice(11, reason.length);
			
			Client.connection.send($pres({
				to: from_room + "/" + nick
			}).c('x', {xmlns: "http://jabber.org/protocol/muc"}));
			
			Client.mucs[from_room] = { "joined":false, "participants":new Array(), "invites_to":new Array(), "nickname":nick};
		}
		
		return true;
	},
	
	on_message: function (message) {
		var from = jQuery(message).attr('from');
		var from_bare = Strophe.getBareJidFromJid(from);
		var to_bare = Strophe.getBareJidFromJid(jQuery(message).attr('to'));
		var jid_id = Client.jid_to_id(from_bare);
		
		var sender_name = jQuery("div").filter(document.getElementById(from_bare)).find('.friends_item_name').text();
		var sender_img_src = jQuery("div").filter(document.getElementById(from_bare)).find('.friends_item_picture').attr("src");
		var sender_id = document.getElementById(from_bare).getElementsByTagName('table')[0].id;
		var timestamp = +new Date;
		
		// do nothing if received data is not from course members
		if (Client.check_membership(from_bare) == false) {
			console.log("message from non-member: " + from_bare);
			return true;
		}
		
		if (jQuery('#chat_' + jid_id).length !== 0){
			var composing = jQuery(message).find('composing');
			if (composing.length > 0) {
				jQuery('#chat_' + jid_id + ' .chat_messages').parent().find('.chat_event').text(sender_name + ' started typing...');
			}
			
			var paused = jQuery(message).find('paused');
			if (paused.length > 0) {
				jQuery('#chat_' + jid_id + ' .chat_messages').parent().find('.chat_event').text();
			}
		}

		var body = jQuery(message).find("html > body");

		if (body.length === 0) {
			body = jQuery(message).find('body');
			if (body.length > 0) {
				body = body.text()
			} else {
				body = null;
			}
		} else {
			body = body.contents();
			var span = jQuery("<span></span>");
			body.each(function () {
				if (document.importNode) {
					jQuery(document.importNode(this, true)).appendTo(span);
				} else {
					// IE workaround
					span.append(this.xml);
				}
			});

			body = span;
		}

		if (body) {
			if (jQuery('#chat_' + jid_id).length !== 0) {					
				// add the new message
				Client.append_new_msg(sender_img_src, sender_id, sender_name, body, timestamp, jid_id);
				
				if (jQuery('a[href="#chat_' + jid_id + '"]').parent().hasClass("ui-state-active") && 
					jQuery('a[href="#chat_' + jid_id + '"]').parent().hasClass("ui-tabs-selected") &&
					jQuery('a[href="#tab_conversations"]').parent().hasClass("ui-tabs-selected") &&
					jQuery('a[href="#tab_conversations"]').parent().hasClass("ui-state-active")) {
				
					Client.focus_chat(jid_id);
					Client.scroll_chat(jid_id);
				}				
			}

			jQuery("div").filter(document.getElementById(from_bare)).data('jid', from);
			
			Client.update_inbox(from_bare, body, timestamp, false);
		}
		
		return true;
	},
	
	on_public_message: function (message) {
		var from = jQuery(message).attr('from');
		var room = Strophe.getBareJidFromJid(from);
		var nick = Strophe.getResourceFromJid(from);
		var jid_id = Client.jid_to_id(room);

		// make sure message is from the right place
		if (Client.mucs[room] != undefined) {
			var notice = !nick;			
			var body = jQuery(message).children('body').text();

			if (!notice) {
				if (jQuery(message).children("delay").length > 0  || jQuery(message).children("x[xmlns='jabber:x:delay']").length > 0) {
					// skip delayed message (we show only from DB)
					return true;
				}
				
                if (jQuery('#chat_' + jid_id).length !== 0) {						
					// add the new message
					var sender_img_src = jQuery("#roster").find(":contains('" + nick + "')").filter("td").parent().find('.friends_item_picture').attr("src");
					if (sender_img_src == undefined) {
						var sender_img = "<div class='pic_square'></div>";
					} else {
						var sender_img = "<img class='picture' src='" + sender_img_src + "' alt='userphoto'/>";
					}
						
					var timestamp = jQuery(message).children("delay").attr('stamp');
					if (timestamp == undefined) {
						var time = "<nobr>" + moment(timestamp).format('HH:mm:ss') + "</nobr>";
					} else  {
						var time = "<nobr>" + moment(timestamp).format('DD.MM.YY') + "</nobr><br/><nobr>" + moment(timestamp).format('HH:mm:ss') + "</nobr>";
					}
						
					var member_id = jQuery("#roster").find(":contains('" + nick + "')").filter("td").parent().parent().parent().attr("id");
					if (member_id == undefined) {
						var profile_link = nick;
					} else {
						var profile_link = "<a href='profile.php?id=" + member_id + "'>" + nick + "</a>";
					}
					
					jQuery('#chat_' + jid_id + ' .chat_messages').append(							
						"<hr/><table><tr>" + 
	         			"<td  class='conversations_picture'>" + 
	                          sender_img + 
	                      	"</td>" + 
	                      	
	                      	"<td  class='conversations_middle'>" + 
	                      	"<label class='conversations_name'>" + profile_link + "</label>" + 
	                      	"<div class='conversations_msg'>" + body + 
						"</div>" + 
	                      	"</td>" + 
	                        	
	                      	"<td class='conversations_time'>" + 
	                       	"<span>" + time + "</span> " +                            
	                       	"</td>" + 
	                    "</tr></table>");
	                    
	                if (jQuery('a[href="#chat_' + jid_id + '"]').parent().hasClass("ui-state-active") && 
						jQuery('a[href="#chat_' + jid_id + '"]').parent().hasClass("ui-tabs-selected") &&
						jQuery('a[href="#tab_conversations"]').parent().hasClass("ui-tabs-selected") &&
						jQuery('a[href="#tab_conversations"]').parent().hasClass("ui-state-active")) {
					
						Client.focus_chat(jid_id);
						Client.scroll_chat(jid_id);
					}    
				}
				
				Client.update_inbox(room, body, +new Date, false);
					
				
			} else if (Client.mucs[room]["joined"] == true) {
				if (jQuery('#chat_' + jid_id).length !== 0) {
					var timestamp = +new Date;
					jQuery('#chat_' + jid_id + ' .chat_messages').append("<hr/><div class='notice'>" + moment(timestamp).format('HH:mm:ss ') + body + "</div>");
					Client.focus_chat(jid_id);
					Client.scroll_chat(jid_id);
				}
			}
		}

		return true;
	},
	
	// 		LOGS ON LOGIN (ex: auth failed)
	log: function (msg) {
		jQuery('#log').append("<p>" + msg + "</p>");
	},
	
	clear_log: function (){
		jQuery('#log').empty();
	},
	
		
	// 		HELPERS
	// shows new added contact in the #roster div;
	// is called as soon as both presences "subscribe" and "subscribed" were received
	// from_bare: jid of contact in bare form
	get_new_contact_data: function (from_bare) {
		var dataString = 'from_bare=' + from_bare;
		jQuery.ajax({
			type: "POST",
			url: "ATutor/mods/chat_new/ajax/get_new_contact_data.php",
			data: dataString,
			cache: false,
			success: function (data) {			
				if (document.getElementById(jQuery(data).attr("id")) == null) {
					jQuery("#roster").prepend(data);
				}				
			},
			error: function (xhr, errorType, exception) {
				console.log("error: " + exception);
			}		
		});
	},
	
	// adds new message notifications to Inbox list and conversation tabs if needed;
	// is called when new message is received (both private and MUC)
	// from_bare: jid of sender in bare form
	// body: message text itself
	// timestamp: time stamp when the message was received
	// sender_me: true if i send the msg, false otherwise
	update_inbox: function (from_bare, body, timestamp, sender_me) {
		var inbox_item = document.getElementById("inbox_" + from_bare);
		var jid_id = Client.jid_to_id(from_bare);
		
		if (from_bare.indexOf('@conference.talkr.im') == -1) {
			// private
			var name = jQuery("div").filter(document.getElementById(from_bare)).find(".friends_item_name")[0].textContent;
			
			var update = "<li>" + moment(timestamp).format('HH:mm:ss') + ": New private message from " + name + ": " + body + "</li>";
			Interface.wai_aria_log(update);
		} else {
			// muc
			var update = "<li>" + moment(timestamp).format('HH:mm:ss') + ": New message in group chat " + Strophe.getNodeFromJid(from_bare) + ": " + body + "</li>";
			Interface.wai_aria_log(update);
		}
	
		if (inbox_item != null) {
			jQuery("li").filter(inbox_item).find(".inbox_list_info").replaceWith("<div class='inbox_list_info'>" + body + "</div>");
			jQuery("li").filter(inbox_item).find(".inbox_list_time")[0].textContent = moment(timestamp).format('HH:mm:ss');
			
			// change order
			jQuery("#inbox_list")[0].removeChild(inbox_item);
			jQuery("#inbox_list").prepend(inbox_item);
		
		} else {
			// add new item
			if (jQuery("#tab_inbox li").length == 0) {
				jQuery("#inbox_notification").remove();
			}
			
			if (from_bare.indexOf('@conference.talkr.im') == -1) {
				// private
				var img_src = jQuery("div").filter(document.getElementById(from_bare)).find("img").attr("src");
				
				var id = jQuery("div").filter(document.getElementById(from_bare)).find("table").attr("id");
				var inbox_item = "<li class='inbox_list_item inbox_private' id='inbox_" + from_bare + "' role='listitem' title='Chat with " + name + "' tabindex='0'" + 
					"aria-controls='inbox_list' onblur='jQuery(this)[0].showFocus = false;' onfocus='jQuery(this)[0].showFocus = true;'" + 
					"onkeydown='return Interface.optionKeyEvent_inbox_list_item(event);' onkeypress='return Interface.optionKeyEvent_inbox_list_item(event);'>" +
		         		"<table><tr>" +
		         				"<td><img class='picture' src='" + img_src + "' alt='userphoto'/></td>" +
		                       	"<td class='inbox_list_middle'>" +
		                        	"<label class='inbox_list_name'><a href='profile.php?id=" + id + "'>" + name + "</a></label>" +
		                        	"<div class='inbox_list_info'>" + body + "</div>"+
		                       	"</td>" +
		                        	
		                       	"<td class='inbox_list_time'><nobr>" + moment(timestamp).format('HH:mm:ss') + "</nobr></td>" +
		                "</tr></table>" +
		            "</li>";
		        
		        jQuery("#inbox_list").prepend(inbox_item);
		        
			} else {
				//muc
				var dataString = 'group_jid=' + from_bare;
				jQuery.ajax({
					type: "POST",
					url: "ATutor/mods/chat_new/ajax/get_inbox.php",
					data: dataString,
					async: false,
					cache: false,
					success: function (returned) {
						var data = returned.split('  ');
						var nr = data[0];
						var base_path = data[1];
						var inbox_item = "<li class='inbox_list_item inbox_muc' id='inbox_" + from_bare + "' role='listitem' title='Group chat " + Strophe.getNodeFromJid(from_bare) + "' tabindex='0'" +
							"aria-controls='inbox_list' onblur='jQuery(this)[0].showFocus = false;' onfocus='jQuery(this)[0].showFocus = true;'" +
							"onkeydown='return Interface.optionKeyEvent_inbox_list_item(event);' onkeypress='return Interface.optionKeyEvent_inbox_list_item(event);'>" +
			         		"<table><tr>" +
			         				"<td><img class='picture' src='" + base_path + "/images/home-acollab.png' alt='group_chat_image'/></td>" +
			                       	"<td class='inbox_list_middle'>" +
			                        	"<label class='inbox_list_name'>" + Strophe.getNodeFromJid(from_bare) + "</label>" +
			                        	"<label class='inbox_list_nr'>(" + nr + " members)</label>" +
			                        	"<div class='inbox_list_info'>" + body + "</div>" +
			                       	"</td>" +
			                        	
			                       	"<td class='inbox_list_time'>" + moment(timestamp).format('HH:mm:ss') + "</td>" +
			                "</tr></table>" +
			            "</li>";
			            
			            jQuery("#inbox_list").prepend(inbox_item);
						
			        },
			        error: function (xhr, errorType, exception) {
			            console.log("error: " + exception);
			        }		
				});
			}
		}
		
		if ((!jQuery('a[href="#tab_conversations"]').parent().hasClass("ui-tabs-selected") &&
			!jQuery('a[href="#tab_conversations"]').parent().hasClass("ui-state-active")) ||
			
			(document.getElementById("inbox_" + from_bare) == null) ||

			(!jQuery('a[href="#chat_' + jid_id + '"]').parent().hasClass("ui-state-active") && 
			!jQuery('a[href="#chat_' + jid_id + '"]').parent().hasClass("ui-tabs-selected") &&
			jQuery('a[href="#tab_conversations"]').parent().hasClass("ui-tabs-selected") &&
			jQuery('a[href="#tab_conversations"]').parent().hasClass("ui-state-active"))) {
			
			// update inbox tab text
		    var nr = jQuery("#inbox_list li").filter(".inbox_list_item_new").length;
			if (nr == 0 && !sender_me) {
				jQuery('a[href="#tab_inbox"]')[0].textContent = "Inbox list (1)";
			} else {				
				var found = false;
				jQuery("#inbox_list li").filter(".inbox_list_item_new").each(function () {
					if (jQuery(this).attr('id').slice(6, jQuery(this).attr('id').length) == from_bare) {
						found = true;
					}			
				});
				if (found == false) {
					nr = parseInt(nr) + 1;
				}
				var nr_match = jQuery('a[href="#tab_inbox"]')[0].textContent.match(/\([0-9999]\)/);
				if (nr_match != null) {
					var len = nr_match[0].length;
					jQuery('a[href="#tab_inbox"]')[0].textContent = jQuery('a[href^="#tab_inbox"]')[0].textContent.slice(0, jQuery('a[href="#tab_inbox"]')[0].textContent.length - len) + "(" + nr + ")";
				}
			}
			jQuery("li").filter(document.getElementById("inbox_" + from_bare)).addClass("inbox_list_item_new");
		}
		
		if ((jQuery('#chat_' + jid_id).length !== 0 &&
			!jQuery('a[href="#chat_' + jid_id + '"]').parent().hasClass("ui-state-active") && 
			!jQuery('a[href="#chat_' + jid_id + '"]').parent().hasClass("ui-tabs-selected") &&
			jQuery('a[href="#tab_conversations"]').parent().hasClass("ui-tabs-selected") &&
			jQuery('a[href="#tab_conversations"]').parent().hasClass("ui-state-active")) ||
			
			(jQuery('#chat_' + jid_id).length !== 0 && 
			!jQuery('a[href="#tab_conversations"]').parent().hasClass("ui-tabs-selected") &&
			!jQuery('a[href="#tab_conversations"]').parent().hasClass("ui-state-active"))) {
		
			jQuery('a[href="#chat_' + jid_id + '"]').parent().addClass("conversation_tab_new_msg");
				
			var tab_text = jQuery('a[href="#chat_' + jid_id + '"]')[0].textContent;
		    var nr = tab_text.match(/\([0-9999]\)/);
			if (nr != null) {
				var len = nr[0].length;
				nr = parseInt(nr[0].slice(0, nr[0].length-1).slice(1, nr[0].length))+1;
				jQuery('a[href="#chat_' + jid_id + '"]')[0].textContent = tab_text.slice(0, tab_text.length - len) + "(" + nr + ")";
			} else {
				jQuery('a[href="#chat_' + jid_id + '"]')[0].textContent = tab_text + " (1)";
			}
			
		}
	},
	
	// shows a contacts' status changes in the MUC roster
	// user_jid: jid of contact in bare form
	// nick: displayed name
	// group: group wher to show changes
	// joined: true if joined, false if left
	muc_user_status_change: function (user_jid, nick, group, joined) {	
		if (user_jid != Strophe.getBareJidFromJid(Client.my_full_jid)) {
			var group_id = Client.jid_to_id(group);
			if (joined == true) {
				var status = "online";
				var css = "class='joined' ";
			} else {
				var status = "offline";
				var css = '';
			}
			
			// add to array if does not exist or change status if exists
			var pos = -1;
			for (var i = 0; i < Client.mucs[group]["participants"].length; i++) {
				if (Client.mucs[group]["participants"][i]["jid"] == user_jid) {
					pos = i;
				}
			}
			if (pos == -1) {
				Client.mucs[group]["participants"].push({"jid": user_jid, "status": status, "nick": nick});
				var status_before = Client.mucs[group]["participants"][Client.mucs[group]["participants"].length - 1]["status"];
			} else {
				var status_before = Client.mucs[group]["participants"][pos]["status"];
				Client.mucs[group]["participants"][pos]["status"] = status;
				Client.mucs[group]["participants"][pos]["nick"] = nick;
			}
			
			// if tab opened, show logs
			if (jQuery('#chat_' + group_id).length !== 0 && joined == true) {
				jQuery('#chat_' + group_id + ' .muc_roster li').each(function () {
					if (jQuery(this).attr('class').search('muclist_' + user_jid) != -1) {
						jQuery(this).addClass("joined");
					}
				});
				
				if (status_before == "offline" && status == "online") {
					var timestamp = +new Date;
					jQuery('#chat_' + group_id).find('.chat_messages').append("<hr/><div class='notice'>" + moment(timestamp).format('HH:mm:ss ') + nick + " joined the room</div>");
				}
				
				if (jQuery('a[href="#chat_' + group_id + '"]').parent().hasClass("ui-state-active") && 
					jQuery('a[href="#chat_' + group_id + '"]').parent().hasClass("ui-tabs-selected") &&
					jQuery('a[href="#tab_conversations"]').parent().hasClass("ui-tabs-selected") &&
					jQuery('a[href="#tab_conversations"]').parent().hasClass("ui-state-active")) {
				
					Client.focus_chat(group_id);
					Client.scroll_chat(group_id);
				}
				
			} else if (jQuery('#chat_' + group_id).length !== 0 && joined == false) {
				jQuery('#chat_' + group_id + ' .muc_roster li').each(function () {
					if (jQuery(this).attr('class').search('muclist_' + user_jid) != -1) {
						jQuery(this).removeClass("joined");
					}
				});
				
				if (status_before == "online" && status == "offline") {
					var timestamp = +new Date;
					jQuery('#chat_' + group_id).find('.chat_messages').append("<hr/><div class='notice'>" + moment(timestamp).format('HH:mm:ss ') + nick + " left the room</div>");
				}
				
				if (jQuery('a[href="#chat_' + group_id + '"]').parent().hasClass("ui-state-active") && 
					jQuery('a[href="#chat_' + group_id + '"]').parent().hasClass("ui-tabs-selected") &&
					jQuery('a[href="#tab_conversations"]').parent().hasClass("ui-tabs-selected") &&
					jQuery('a[href="#tab_conversations"]').parent().hasClass("ui-state-active")) {
					
					Client.focus_chat(group_id);
					Client.scroll_chat(group_id);
				}
			}
		}
	},
	
	// shows older messages in .chat_messages div, binds function to .chat_messages that will retrieve older history on scrollTop;
	// is called for private and MUC chat tabs
	// from_bare: private chat: jid of contact in bare form; MUC: null
	// to_bare: private chat: my jid in bare form; MUC: jid of contact in bare form
	// jid_id: jid in id form - div where to load
	load_older_messages: function (from_bare, to_bare, jid_id) {
		if (from_bare == null) {			
			var dataString = 'to=' + to_bare + '&my_jid=' + Strophe.getBareJidFromJid(Client.my_full_jid);
			jQuery.ajax({
				type: "POST",
				url: "ATutor/mods/chat_new/ajax/get_muc_roster.php",
				data: dataString,
				cache: false,
				success: function (data) {
					// add muc roster
					jQuery('#chat_' + jid_id + ' .muc_roster ul').append(jQuery(data).find('li'));
					
					var jids = new Array();
					jQuery('#chat_' + jid_id + ' .muc_roster ul').find('li').each(function () {
						if (jQuery(this).attr('class') != 'muc_roster_me') {
							var jid = jQuery(this).attr('class').slice(8, jQuery(this).attr('class').length);
							if (jQuery("div").filter(document.getElementById(jid)).hasClass("online") == true) {
								jQuery(this).addClass("joined");
							} else {
								var css = '';
							}
							
							var pos = -1;
							for (var i = 0; i < Client.mucs[to_bare]["participants"].length; i++) {
								if (Client.mucs[to_bare]["participants"][i]["jid"] == jid) {
									pos = i;
								}
							}
							
							if (jQuery("div").filter(document.getElementById(jid)).hasClass("online") == true) {
								var status = "online";
							} else {
								var status = "offline";
							}							
							if (pos == -1) { 
								jids.push({"jid": jid, "nick": "", "status": status});
							}
						}
					});
					
					Client.mucs[to_bare]["participants"] = jids;
					
		        },
		        error: function (xhr, errorType, exception) {
		            console.log("error: " + exception);
		        }		
			});			
			var dataString = 'to=' + to_bare;			
		} else {
			var dataString = 'from=' + from_bare + '&to=' + to_bare;
		}		
		jQuery.ajax({
			type: "POST",
			url: "ATutor/mods/chat_new/ajax/get_older_messages.php",
			data: dataString,
			cache: false,
			success: function (data) {		
				var timestamps = jQuery(data).find('.conversations_time');
				timestamps.each(function () {
					var timestamp = Number(jQuery(this).text());
					data = data.replace(timestamp, "<nobr>" + moment(timestamp).format('DD.MM.YY') + "</nobr><br/><nobr>" + moment(timestamp).format('HH:mm:ss') + "</nobr>");
				});
						
				jQuery('#chat_' + jid_id + ' .chat_messages').append(data);
						
				// binding function that will retrieve older messages on scrollTop
				jQuery('#chat_' + jid_id + ' .chat_messages').scroll(function(){
					if (jQuery(this).scrollTop() == 0) {						
						var real_height = jQuery('#chat_' + jid_id + ' .chat_messages').get(0).scrollHeight;
								
						// load older messages
						var offset = jQuery('#chat_' + jid_id + ' .chat_messages').find('table').length;
						if (from_bare == null) {
							var dataString = 'to=' + to_bare + '&offset=' + offset;
						} else {
							var dataString = 'from=' + from_bare + '&to=' + Strophe.getBareJidFromJid(Client.my_full_jid) + '&offset=' + offset;
						}
						jQuery.ajax({
							type: "POST",
							url: "ATutor/mods/chat_new/ajax/get_older_messages.php",
							data: dataString,
							cache: false,
							success: function (data) {
								var timestamps = jQuery(data).find('.conversations_time');
								timestamps.each(function () {
									var timestamp = Number(jQuery(this).text());
									data = data.replace(timestamp, "<nobr>" + moment(timestamp).format('DD.MM.YY') + "</nobr><br/><nobr>" + moment(timestamp).format('HH:mm:ss') + "</nobr>");
								});
										
								jQuery('#chat_' + jid_id + ' .chat_messages').prepend(data);
										
								var real_height_after = jQuery('#chat_' + jid_id + ' .chat_messages').get(0).scrollHeight;
								jQuery('#chat_' + jid_id + ' .chat_messages').scrollTop(real_height_after - real_height);
							},
							error: function (xhr, errorType, exception) {
							    console.log("error: " + exception);
							}		
						});
					}
				});  
						
				if (jQuery('a[href="#chat_' + jid_id + '"]').parent().hasClass("ui-state-active") && 
					jQuery('a[href="#chat_' + jid_id + '"]').parent().hasClass("ui-tabs-selected") &&
					jQuery('a[href="#tab_conversations"]').parent().hasClass("ui-tabs-selected") &&
					jQuery('a[href="#tab_conversations"]').parent().hasClass("ui-state-active")) {
				
					Client.focus_chat(jid_id);
					Client.scroll_chat(jid_id);
				}
			},
			error: function (xhr, errorType, exception) {
			    console.log("error: " + exception);
			}		
		});
	},
	
	// shows a contacts' status changes in #roster div
	// elem_roster: html element of element to change status
	// online: true if joined, false if left
	replace_contact: function (elem_roster, online) {
		if (online == true){			
			group_el_roster = jQuery('#roster').find('.online');
			group = 'online';
			group_other = 'offline';
			
			if (elem_roster.className.search('me') > 0) {
				elem_roster.className = "friends_column_wrapper online me";
			} else {
				elem_roster.className = "friends_column_wrapper online";
				jQuery(elem_roster).attr('title', jQuery(elem_roster).find('.friends_item_name').text() + " - Online");
				
				if (Client.roster[jQuery(elem_roster).attr('id')] != 'online') {
					Interface.wai_aria_log(moment(+new Date).format('HH:mm:ss') + ": Status update: " + jQuery(elem_roster).find(".friends_item_name").text() + " is online");
					Client.roster[jQuery(elem_roster).attr('id')] = 'online';
				}
			}
			jQuery("div").filter(elem_roster).find("td")[2].textContent = "Online";
		} else {
			group_el_roster = jQuery('#roster').find('.offline');
			group = 'offline';
			group_other = 'online';
			
			if (elem_roster.className.search('me') > 0) {
				elem_roster.className = "friends_column_wrapper offline me";
			} else {
				elem_roster.className = "friends_column_wrapper offline";
				jQuery(elem_roster).attr('title', jQuery(elem_roster).find('.friends_item_name').text() + " - Offline");
				
				if (Client.roster[jQuery(elem_roster).attr('id')] != 'offline') {
					Interface.wai_aria_log(moment(+new Date).format('HH:mm:ss') + ": Status update: " + jQuery(elem_roster).find(".friends_item_name").text() + " is offline");
					Client.roster[jQuery(elem_roster).attr('id')] = 'offline';
				}
			}			
			jQuery("div").filter(elem_roster).find("td")[2].textContent = "";
		}
				
		jQuery('#roster')[0].removeChild(elem_roster);
		
		if (group_el_roster.length > 0) {
			var name_roster = elem_roster.getElementsByTagName("td")[1].textContent;
			var inserted = false;
			group_el_roster.each(function () {
				var cmp_name = jQuery(this).find('.friends_item_name')[0].textContent;
				if (name_roster < cmp_name) {
					jQuery(this).before(elem_roster);
					inserted = true;
					return false;
				}
			});
			if (!inserted) {
				// insert after last element of group
				jQuery('#roster').find('.' + group).last().after(elem_roster);	
			}
		} else {
			if (group == 'online'){
				jQuery('#roster').prepend(elem_roster);
			} else if (group == 'offline'){
				jQuery('#roster').append(elem_roster);
			}
		}
	},
	
	// add element with current user data in #roster div when we authorize in chat for the first time
	// jid: jid of current user
	// name: name to display
	// pic: profile icon URL
	// id: ATutor member_id from AT_members table that corresponds to the user
	show_new_contact: function (jid, name, pic, id) {
		group_el = jQuery('.online');
		if (jQuery("div").filter(jQuery("#chat").find("div")[1]).attr("id") == id) {
			var css_class = " me";
		} else {
			var css_class = "";
		}
		
		to_insert = "<div class='friends_column_wrapper online" + css_class + "' id=" + jid + ">" + 
	                    	"<table class='friends_item' id='" + id + "'><tr>" + 
	         					"<td><img src='" + pic + "' class='friends_item_picture' alt='userphoto'/></td>" +
	                        	"<td class='friends_item_name'>" + name + "</td>" + 
	                        	"<td class='friends_item_status'>Online</td>" +
	                    	"</tr></table>" + 
	              		"</div>";
	    
	    if (jQuery('.online').length == 0 && jQuery('.offline').length == 0) {
			jQuery("#roster")[0].textContent = "";
		}          		
	    
		if (group_el.length > 0) {
			var inserted = false;
			group_el.each(function () {
				var cmp_name = jQuery(this).find('.friends_item_name')[0].textContent;
				if (name < cmp_name) {
					jQuery(this).before(to_insert);
					inserted = true;
				}
			});

			if (!inserted) {
				// insert after last element of group
				jQuery("#roster").append(to_insert);
			}
		} else if (jQuery('.offline').length > 0) {
			jQuery('.' + 'offline').first().before(to_insert);
		} else {
			jQuery("#roster").append(to_insert);
		}
		
		// group chat tab
		var group_chat_item = "<div class='friends_column_wrapper_classmates_me' id='classmates_'" + jid + "'>" + 
					    	"<table class='friends_item'><tr>"+ 
	         					"<td><img class='friends_item_picture' src=" + pic + " alt='userphoto'/></td>" + 
	                        	"<td class='friends_item_name'>" + name + "</td>"
                    		"</tr></table>" + 
	              		"</div>";
		jQuery("#friends_members").append(group_chat_item);
	},
	
	// checks if user with jid is current course and chat member
	// true if is member, false otherwise
	check_membership: function (jid) {
		var dataString = 'jid=' + jid;
		jQuery.ajax({
			type: "POST",
			url: "ATutor/mods/chat_new/ajax/check_membership.php",
			data: dataString,
			cache: false,
			success: function (returned) {
				if (returned == 1){
					return true;
				} else {
					return false;
				}
			},
			error: function (xhr, errorType, exception) {
			    console.log("error: " + exception);
			}		
		});
	},
	
	// writes message that is going to be sent into ATutor DB for both private and MUC chats
	// from: jid of current user
	// to: destinator (contact or MUC)
	// msg: message text
	// timestamp: time stamp when the message was sent
	message_to_db: function (from, to, msg, timestamp, groupchat) {
		var dataString = 'from=' + from + '&to=' + to + '&msg=' + msg + '&timestamp=' + timestamp + '&groupchat=' + groupchat;
		jQuery.ajax({
			type: "POST",
			url: "ATutor/mods/chat_new/ajax/new_message.php",
			data: dataString,
			cache: false,
			success: function (returned) {
				if (returned != 1) {
					console.log('An error while saving message into database occured.');
				}
			},
			error: function (xhr, errorType, exception) {
			    console.log("error: " + exception);
			}		
		});
	},
	
	// transforms jid in bare form to jid_id that is used in the interface
	jid_to_id: function (jid) {
		return Strophe.getBareJidFromJid(jid)
			.replace(/@/g, "-")
			.replace(/\./g, "-");
	},
	
	// appends the message and removes "started typing" notification in private chats
	// sender_img_src: URL of sender icon
	// sender_id: ATutor member_id from AT_members table that corresponds to the user
	// sender_name: name to display
	// timestamp: time stamp when the message was sent
	// jid_id: jid in id form - div where to load
	append_new_msg: function (sender_img_src, sender_id, sender_name, body, timestamp, jid_id) {	
		jQuery('#chat_' + jid_id + ' .chat_messages').append(
						"<hr/><table><tr>" + 
         					"<td  class='conversations_picture'>" + 
                            "<img class='picture' src='" + sender_img_src + "' alt='userphoto'/>" + 
                        	"</td>" + 
                        	
                        	"<td  class='conversations_middle'>" + 
                        	"<label class='conversations_name'><a href='profile.php?id=" + sender_id + "'>" + sender_name + "</a></label>" + 
                        	"<div class='conversations_msg'>" + body + 
							"</div>" + 
                        	"</td>" + 
                        	
                        	"<td class='conversations_time'>" + 
                        	"<span><nobr>" + moment(timestamp).format('HH:mm:ss') + "</nobr></span> " +                            
                        	"</td>" + 
                        "</tr></table>");
				
		// remove notifications since user is now active
		jQuery('#chat_' + jid_id + ' .chat_messages').parent().find('.chat_event').text('');
	},

	// scrolls the .chat_messages div of jid_id tab to the latest message
	scroll_chat: function (jid_id) {
		var div = jQuery('#chat_' + jid_id + ' .chat_messages').get(0);
		if (div != undefined) {
			div.scrollTop = div.scrollHeight;
		}
	},
	
	// selects tab with jid_id and focuses textarea
	focus_chat: function (jid_id) {
		jQuery('#tabs').tabs('select', '#tab_conversations');
		jQuery('#subtabs').tabs('select', '#chat_' + jid_id);
		jQuery('#chat_' + jid_id + ' textarea').focus();
	},
	
	// returns message text with links highlighted
	return_links: function (message) {
		var exp = /(\b((https?|ftp|file):\/\/|www.)[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
    	return message.replace(exp,"<a href='$1'>$1</a>");
	}
};
/*
jQuery(window).unload(function() {
	alert("unload");
	//Client.connection.sync = true; // Switch to using synchronous requests since this is typically called onUnload.
	Client.connection.pause();
	//Client.connection.flush();
	Client.connection.disconnect();
	return false;
});*/

// connection
jQuery(document).bind('connect', function (ev, data) {
	// get value from cookies 		
	//var conn_sid = jQuery.cookie("conn_sid");
	//var conn_rid = jQuery.cookie("conn_rid");
	//var conn_jid = jQuery.cookie("conn_jid");
	//console.log("FROM COOKIE: ", conn_sid, conn_rid, conn_jid);

	var conn = new Strophe.Connection("http://bosh.metajack.im:5280/xmpp-httpbind");
	
	if (document.getElementById('peek') != null) {
		conn.xmlInput = function (body) {
		    Console.show_traffic(body, 'incoming');
		};
		conn.xmlOutput = function (body) {
		    Console.show_traffic(body, 'outgoing');
		};
	}
	
	//if (conn_sid == null && conn_rid == null && conn_jid == null){
	if (true){	    
		conn.connect(data.jid, data.password, function (status) {
			if (status === Strophe.Status.CONNECTED) {
				var course_members_jids = new Array();
				if (data.id != undefined){
					// make db entry if not exists yet
					var course_id = jQuery('#chat')[0].getElementsByTagName("div")[0].id;
					var dataString = 'id=' + data.id + '&jid=' + data.jid + '&pass=' + data.password + '&course_id=' + course_id;
					jQuery.ajax({
						type: "POST",
						url: "ATutor/mods/chat_new/ajax/check_auth.php",
						data: dataString,
						cache: false,
						success: function (returned) {						
							if (returned == 0){
								console.log('Error: Cannot insert!!.');
								
							} else {
								document.getElementById('welcome').style.display = 'none';
								jQuery('#chat').show();
								
								// add div to side box menu
								var data = returned.split(' ');
								var jid = data[0];
								var name = data[1] + ' ' + data[2];
								var pic = data[3];
								var id = data[4];
								Client.show_new_contact(jid, name, pic, id);
								
								course_members_jids = data.slice(5, data.length);
								
								jQuery(document).trigger('connected', [course_members_jids]);
							}
				        },
				        error: function (xhr, errorType, exception) {
				            console.log("error: " + exception);
				        }		
					});
				} else {
					// store connection into cookies for later use
					//jQuery.cookie("conn_sid", conn.sid, {expires: 365, path: '/'});
					//jQuery.cookie("conn_rid", conn.rid, {expires: 365, path: '/'});
					//jQuery.cookie("conn_jid", conn.jid, {expires: 365, path: '/'});
					//console.log("WROTE TO COOKIES: ", conn.sid, conn.rid, typeof(conn.jid), conn.jid);
			
					jQuery(document).trigger('connected', [course_members_jids]);
				}
			} 
			else if (status === Strophe.Status.AUTHFAIL) {
				jQuery(document).trigger('authfail');
			} 
			else if (status === Strophe.Status.DISCONNECTED) {
				jQuery(document).trigger('disconnected');
			}
		});
		Client.connection = conn;
		
	} else {
		//console.log("GET FROM COOKIE: ", conn_sid, conn_rid, conn_jid);
		//conn.attach(conn_jid, conn_sid, parseInt(conn_rid) + 1);
		
		jQuery(document).trigger('connected');
	}
});


// XMPP statuses
jQuery(document).bind('connected', function (event, course_members_jids) {
	console.log("Connection established.");
	
	Interface.wai_aria_log(moment(+new Date).format('HH:mm:ss') + ": Connection established");
	
	Client.my_full_jid = Client.connection.jid;
	Client.connection.send($pres().c('show').t(Client.my_full_jid));
	Client.last_presence_sent = +new Date;		
	
    var iq_roster = $iq({type: 'get'}).c('query', {xmlns: 'jabber:iq:roster'});
    Client.connection.sendIQ(iq_roster, Client.on_roster);
    //Client.connection.addHandler(Client.on_presence_subscribe, null, "presence", "subscribe");
	//Client.connection.addHandler(Client.on_presence_subscribed, null, "presence", "subscribed");
    Client.connection.addHandler(Client.on_presence, null, "presence", null);
    Client.connection.addHandler(Client.on_message, null, "message", "chat");
    Client.connection.addHandler(Client.on_public_message, null, "message", "groupchat");
    Client.connection.addHandler(Client.on_muc_invite, null, "message", "normal");    
    
    
    // send subscription request to all course members (on first login course_members_jids.length > 0)
    if (course_members_jids.length > 0) {
    	for (i = 0; i < course_members_jids.length; ++i) {
			Client.connection.send($pres({
				to: course_members_jids[i],
				"type": "subscribe"}));
		}
	}    
	
	jQuery('#buttonbar').find('input').removeAttr('disabled');
    jQuery('#console_input').removeClass('disabled').removeAttr('disabled');
    
	document.body.style.cursor = "auto";
	jQuery("#dialog_message").dialog('close');
	jQuery('.friends_column_wrapper').live('click', Interface.on_friends_column_wrapper);
	jQuery('.inbox_list_item').live('click', Interface.on_inbox_list_item);
	
	// enter all mucs
	var dataString = 'id=' + jQuery("div").filter(jQuery('#chat').find('div')[1]).attr('id');
	jQuery.ajax({
		type: "POST",
		url: "ATutor/mods/chat_new/ajax/get_mucs.php",
		data: dataString,
		cache: false,
		success: function (data) {
			console.log("MUCs: ", data);
			if (data != "") {
				var mucs = data.split("  ");
				var my_groupname = jQuery('.me').find('.friends_item_name')[0].textContent;
				for (var i = 0; i < mucs.length; i++) {				
					Client.mucs[mucs[i]] = { "joined":false, "participants":new Array(), "invites_to":new Array(), "nickname":my_groupname};
					Client.connection.send($pres({
						to: mucs[i] + "/" + Client.mucs[mucs[i]]["nickname"]
					}).c('x', {xmlns: "http://jabber.org/protocol/muc"}));
				}
			}
        },
        error: function (xhr, errorType, exception) {
            console.log("error: " + exception);
        }		
	});
});

jQuery(document).bind('authfail', function () {
	console.log("Authentication failed.");
	Client.log("Authentication failed.");
	// remove dead connection object
	Client.connection = null;
	
	jQuery('.button').attr('disabled', 'disabled');
    jQuery('#input').addClass('disabled').attr('disabled', 'disabled');
	document.body.style.cursor = "auto";
	jQuery("#dialog_message").dialog('close');
});

jQuery(document).bind('disconnected', function () {
	console.log("Connection terminated.");
	// remove dead connection object
	Client.connection = null;
	
	jQuery('#buttonbar').find('input').attr('disabled', 'disabled');
    jQuery('#console_input').addClass('disabled').attr('disabled', 'disabled');
});

jQuery(document).bind('room_joined', function (ev, jid) {	
	Client.mucs[jid]["joined"] = true;
	
	if (Client.mucs[jid]["invites_to"].length > 0) {
		Interface.open_conversation_tab(jid, Strophe.getNodeFromJid(jid), true);
	}
	
});

