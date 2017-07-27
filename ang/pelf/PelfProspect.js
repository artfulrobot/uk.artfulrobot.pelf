(function(angular, $, _) {

  // Nb. directive MUST start with lowercase letter.
  angular.module('pelf').directive('pelfProspect', function(crmApi, $timeout, $q) {
    // ? how to tell it that it propsect is needed? where is the prospect thing?
    return {
      // The prospect (Activity.getPelfProspect) is fed in via attribute.
      scope: {
        prospect: '='
      },
      // This directive has its own controller.
      controller: ['$scope', '$location', function ($scope, $location) {
        console.log("prospect controller running", $scope);
        $scope.crmUrl = CRM.url;

        if (!$scope.prospect) {
          // e.g. user entered wrong URL.
          console.warn("prospect controller - NO PROSPECT!");
          return;
        }
        // This just simplifies the code below.
        var prospect = $scope.prospect;

        // Ensure est_amount is a float.
        prospect.est_amount = parseFloat(prospect.est_amount);
        prospect.scale = parseFloat(prospect.scale);
        $scope.estWorth = function() { return Math.round(prospect.est_amount * prospect.scale / 100, 0); };
        $scope.sumFunding = function() {
          return Math.round(_.reduce(prospect.funding, function(tot, row) { return tot+parseFloat(row.amount); }, 0) ,0);
        };
        $scope.formatDate = CRM.utils.formatDate;
        $scope.getStage = function() {
          return _.find(prospect.stages, {value: prospect.stage}).label;
        };

        $scope.editData = false;
        // Edit mode start.
        $scope.editStart = function() {
          console.log("orgsListEditSave4", $scope.orgsListEditSave);
          $scope.editData = {
            name: prospect.subject,
            est_amount: prospect.est_amount,
            scale: prospect.scale,
            stage: prospect.stage,
            details: prospect.details,
            when: prospect.date.substr(0, 10),
          };
          if ($scope.orgsListEditStart) {
            // This is not defined yet in the case of a new prospect.
            $scope.orgsListEditStart();
          }
        };
        // Save edits.
        $scope.editSave = function() {

          var isNewProspect = (prospect.id === null);
          console.log("orgsListEditSave1", $scope.orgsListEditSave);

          var params = {
            subject: $scope.editData.name,
            when: $scope.editData.when,
            details: $scope.editData.details,
            activity_type_id: prospect.activity_type_id
          };
          if (!isNewProspect) {
            params.id = prospect.id;
          }

          params[prospect.field_map.est_amount] = $scope.editData.est_amount;
          params[prospect.field_map.scale] = $scope.editData.scale;
          params[prospect.field_map.stage] = $scope.editData.stage;

          // Note to self.  crmApi returns a *promise*, not a function that
          // *returns* a promise.  So you can't chain them like
          // crmApi().then(crmApi()).then(crmApi())...  because then() expects
          // a function (or two) that receive a result parameter.  So you need
          // to wrap it in another function that returns the crmApi() promise
          // result.

          var q = $q.when()
          .then(function() { return crmApi('Activity', 'create', params); })
          .then(function(result) {
            console.log("orgsListEditSave2", $scope.orgsListEditSave);
            console.log("updating UI after save ",result, params);
            // Update the ID (essential for when we've just created a new prospect).
            prospect.id = result.id;
            prospect.subject = $scope.editData.name;
            prospect.est_amount = $scope.editData.est_amount;
            prospect.scale = $scope.editData.scale;
            prospect.stage = $scope.editData.stage;
            prospect.details = $scope.editData.details;
            prospect.date = $scope.editData.when;
            console.log("prospect after update from save ",prospect);
            console.log("orgsListEditSave3", $scope.orgsListEditSave);
          });

          // Now we know the activity is saved, we can save the targets.
          q.then(function() {
            return $scope.orgsListEditSave();
          })
          .then(function() {
            console.log("final thing");
            if (isNewProspect) {
              // Redirect to proper path for this prospect.
              $location.path("/pelf/prospect/" + prospect.id);
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
          $scope.orgsListEditCancel();
          $scope.editData = false;
        };

        if (!prospect.id) {
          // Open new prospects in edit mode.
          $scope.editStart();
        }

      }], // end of controller.
      link: function(scope, elem, attrs) {
        if (true) return;
        if (scope.prospect && !scope.prospect.id) {
          $timeout(function() {
            elem.find('input[name="prospect_name"]').focus();
          });
        }
      },
      templateUrl: '~/pelf/PelfProspect.html',
    };
  });

})(angular, CRM.$, CRM._);
