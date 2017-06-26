(function(angular, $, _) {

  angular.module('pelf').controller('PelfProspectCtrl', function PelfProspectCtrl($scope, prospect) {
    console.log("PelfProspectCtrl ", prospect);
    $scope.prospectId = prospect;
  });

})(angular, CRM.$, CRM._);

