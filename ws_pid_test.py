#! /usr/bin/env python
# -*- coding: utf-8 -*-
# Written by Romain Thirion 2016
# License: GPL v3

from time import *
import time
import threading
import locale

# Dependance: sudo pip install git+https://github.com/dpallot/simple-websocket-server.git
from SimpleWebSocketServerMod import SimpleWebSocketServer, WebSocket

# Localisation FR (Format Date)
locale.setlocale(locale.LC_TIME, "fr_FR.utf8")

# Pour les tests, affichages dans la console
DEBUG = True

# OBJET SocketClientThread : Gestion d'une demande client par socket
class WebSocketClientThread(WebSocket):
    def handleMessage(self):
        if self.data=="P": # POSITION
            pos_r=str(49.123456789)+'|'+str(5.987654321)
            if DEBUG: print "Recu commande POS, retourne ", pos_r
            self.sendMessage(pos_r)
        elif self.data=="E": # END
            sys.exit()
            

    def handleConnected(self):
        if DEBUG: print self.address, ' connecté'

    def handleClose(self):
        if DEBUG: print self.address, ' parti'


##### ROUTINE PRINCIPALE #####
if __name__ == '__main__':
        
    # Initialisation du socket serveur
    server = SimpleWebSocketServer('', 7981, WebSocketClientThread)
    clientThread = threading.Thread(target=server.serveforever)
    #clientThread.setDaemon(True) # don't hang on exit
    clientThread.start()
    try:
        while True: # boucle principale
            print strftime('%H:%M:%S Z',time.localtime())
            time.sleep(10)
           
    except (KeyboardInterrupt, SystemExit): #Arrêt par opérateur
        if DEBUG: print "\nKilling all Threads..."
        server.close()
        clientThread.join()

