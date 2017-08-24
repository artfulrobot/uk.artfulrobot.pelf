(function(angular, $, _) {

  // Nb. directive MUST start with lowercase letter.
  angular.module('pelf').directive('pelfContract', ['crmApi', '$timeout', '$q', 'pelf', function(crmApi, $timeout, $q, pelf) {
    return {
      // The contract (Activity.getPelfContract) is fed in via attribute.
      scope: {
        contract: '='
      },
      // This directive has its own controller.
      controller: ['$scope', '$location', 'pelf', function ($scope, $location, pelf) {
        console.log("contract controller running", $scope);
        $scope.crmUrl = CRM.url;
        $scope.pelf = pelf;

        if (!$scope.contract) {
          // e.g. user entered wrong URL.
          console.warn("contract controller - NO contract!");
          return;
        }
        // This just simplifies the code below.
        var contract = $scope.contract;

        $scope.sumFunding = function() {
          return Math.round(_.reduce(contract.funding, function(tot, row) { return tot+parseFloat(row.amount); }, 0) ,0);
        };
        $scope.formatDate = CRM.utils.formatDate;

        $scope.editData = false;
        // Edit mode start.
        $scope.editStart = function() {
          $scope.editData = {
            name: contract.subject,
            details: contract.details,
            when: contract.date.substr(0, 10),
          };
          if ($scope.contactListEditStart) {
            // This is not defined yet in the case of a new contract.
            $scope.contactListEditStart();
          }
        };
        // Save edits.
        $scope.editSave = function() {

          var isNewContract = (contract.id === null);

          var params = {
            subject: $scope.editData.name,
            when: $scope.editData.when,
            details: $scope.editData.details,
            activity_type_id: contract.activity_type_id
          };
          if (!isNewContract) {
            params.id = contract.id;
          }

          // Note to self.  crmApi returns a *promise*, not a function that
          // *returns* a promise.  So you can't chain them like
          // crmApi().then(crmApi()).then(crmApi())...  because then() expects
          // a function (or two) that receive a result parameter.  So you need
          // to wrap it in another function that returns the crmApi() promise
          // result.

          var q = $q.when()
          .then(function() { return crmApi('Activity', 'create', params); })
          .then(function(result) {
            console.log("updating UI after save ",result, params);
            // Update the ID (essential for when we've just created a new contract).
            contract.id = result.id;
            contract.subject = $scope.editData.name;
            contract.details = $scope.editData.details;
            contract.date = $scope.editData.when;
          });

          // Now we know the activity is saved, we can save the targets.
          q.then(function() {
            return $scope.contactListEditSave();
          })
          .then(function() {
            console.log("final thing");
            if (isNewContract) {
              // Redirect to proper path for this contract.
              $location.path("/pelf/contract/" + contract.id);
              $location.replace();
            }
            else {
              // Stop editing.
              $scope.editData = false;
            }
          });
        };
        // Cancel edits.
        $scope.editCancel = function (){
          $scope.contactListEditCancel();
          $scope.editData = false;
        };

        if (!contract.id) {
          // Open new contracts in edit mode.
          $scope.editStart();
        }

      }], // end of controller.
      link: function(scope, elem, attrs) {
        if (true) return;
        if (scope.contract && !scope.contract.id) {
          $timeout(function() {
            elem.find('input[name="contract_name"]').focus();
          });
        }
      },
      templateUrl: '~/pelf/Contract.html',
    };
  }]);

})(angular, CRM.$, CRM._);
