## KatoMarika
Bot IRC pour le channel #pathey, codé en PHP. Il est nécessaire qu'il soit OP du channel pour avoir accès à la plupart des fonctionnalités
#Liste des commandes
+.help : Affiche la lsite des commandes
+.op : Affiche les utilisateurs considérés comme OP du bot
  +.op *pseudo1* [*pseudo2*, *pseudo3*,...] : Ajoute *pseudo1* en tant qu'OP, à refaire à chaque connection du bot.  
   Les OP channel sont automatiquement considérés comme OP du bot à leur connection
+.deop *pseudo* : Enlève *pseudo* de la liste des op
+.unop : enlève le statut d'OP channel du bot
+.voice *pseudo* : Autorise *pseudo* à parler sur un channel modéré. Si *pseudo* est enregistré auprès de chanserv, il obtient le droit de parler à chacune de ses connections
+.unvoice *pseudo* : Enlève les droits de *pseudo* de parler sur un channel modéré
+.prefixe *nouveauPrefixe* : Change le préfixe des commandes du bot (**.** est le préfixe par défaut
+.autokick : Active ou désactive l'autokick
  +.autokick *delai* *messages* :  Change le nombres de *messages* autorisés en *delai* secondes (par défaut, 6 messages en 10 secondes)
  +.autokick *help* : Affiche si l'autokick est activé ou non ainsi que le nombres de messages maximum et l'intervalle de temps
+.trivia : Active ou désactive le trivia et l'autokick (le trivia est géré par un autre bot)
+.say *plouf* : Écrit *plouf* dans l'irc
+.test *plouf* : Écrit le pseudo, l'ident et l'IP de la personne qui a fait les commande, suivit de *plouf* 
