INSERT INTO `__prefix__mailbox_conversation` (`id`, `initiatorId`, `interlocutorId`, `subject`, `read`, `deleted`, `viewed`, `notificationSent`, `createStamp`, `initiatorDeletedTimestamp`, `interlocutorDeletedTimestamp`, `lastMessageId`, `lastMessageTimestamp`) VALUES
(1, __sender_id__, __recipient_id__, 'mailbox_chat_conversation', 3, 0, 3, 1, 1556690564, 0, 0, 1, 1556690604);

INSERT INTO `__prefix__mailbox_message` (`id`, `conversationId`, `timeStamp`, `updateStamp`, `senderId`, `recipientId`, `text`, `recipientRead`, `isSystem`, `wasAuthorized`, `tempId`) VALUES
(1, 1, 1556690604, 1556690637, __sender_id__, __recipient_id__, ' __message__ ', 1, 0, 1, NULL);

INSERT INTO `__prefix__mailbox_last_message` (`conversationId`, `initiatorMessageId`, `interlocutorMessageId`) VALUES
(1, 1, 0);

TRUNCATE `__prefix__mailbox_user_last_data`;
