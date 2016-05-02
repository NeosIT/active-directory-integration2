(function () {
    app.service('NotificationService', NotificationService);

    NotificationService.$inject = ['ngNotify'];

    function NotificationService(ngNotify) {
        return {
            isMessage: isMessage,
            showMessage: showMessage
        };

        /**
         * Check if the given data contains the isMessage flag.
         *
         * @param data
         *
         * @returns {boolean}
         */
        function isMessage(data) {
            return (typeof data['isMessage'] != 'undefined');
        }

        /**
         * Check if the given data is a message. If so, trigger the ngNotify messages
         *
         * @param data
         */
        function showMessage(data) {
            // if the given data is no message, do not notify anything
            if (!this.isMessage(data)) {
                return;
            }

            var message = data['message'];
            var type = data['type'];

            ngNotify.set(message, type)
        }
    }
})();