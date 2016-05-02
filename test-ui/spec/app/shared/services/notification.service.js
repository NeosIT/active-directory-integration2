describe('NotificationService', function () {
    var sut, ngNotity;

    beforeEach(angular.mock.module('adi2-module'));

    beforeEach(inject(function (_ngNotify_, _NotificationService_) {
        sut = _NotificationService_;
        ngNotity = _ngNotify_;
    }));

    it('#isMessage without isMessage flag returns false', function () {
        var actual = sut.isMessage({});

        expect(actual).toBe(false);
    });

    it('#isMessage with isMessage flag returns true', function () {
        var actual = sut.isMessage({isMessage: true});

        expect(actual).toBe(true);
    });

    it('#showMessage smoke test', function () {
        var messageObject = {
            isMessage: true,
            message: 'hello world',
            type: 'success'
        };

        spyOn(ngNotity, 'set');

        sut.showMessage(messageObject);

        // its just a smoke test, but ne need an expectation
        expect(true).toBe(true);
    });

    it('#showMessage with message delegates call to ngNotify', function () {
        var messageObject = {
            isMessage: true,
            message: 'hello world',
            type: 'success'
        };

        spyOn(sut, 'isMessage').and.returnValue(true);
        spyOn(ngNotity, 'set');

        sut.showMessage(messageObject);

        expect(ngNotity.set).toHaveBeenCalledWith(messageObject['message'], messageObject['type']);
    });

    it('#showMessage without message does not delegate call to ngNotify', function () {
        var messageObject = {
            isMessage: true,
            message: 'hello world',
            type: 'success'
        };

        spyOn(sut, 'isMessage').and.returnValue(false);
        spyOn(ngNotity, 'set');

        sut.showMessage(messageObject);

        expect(ngNotity.set).not.toHaveBeenCalledWith(messageObject['message'], messageObject['type']);
    });
});