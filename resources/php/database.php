<?php
class database {

	// Connection Functions
	function connect() {
		$user_agent = getenv("HTTP_USER_AGENT");

		if(strpos($user_agent, "Win") !== FALSE) { 
			$dbhost = "";
			$dbuser = "root";
			$dbpass = "";
			$dbname = "collegebook";	
		} elseif(strpos($user_agent, "Mac") !== FALSE) {
			$dbhost = "";
			$dbuser = "root";
			$dbpass = "root";
			$dbname = "collegebook";
		}
		$connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
		// Testing connection success/failure
		if(mysqli_connect_errno()) {
			die("Database connection failed: " . mysqli_connect_error() . " (" . mysqli_connect_errno() . ")"); 
		}
		return $connection;
	}

	function close($connection) { mysqli_close($connection); }

	
	
	// Common DB call functions
	function getRequests($connection, $id) {
        $requests = array();
		
		$query = "Select * From request Where requesterID = '$id' Or requesteeID = '$id'";
        $result = mysqli_query($connection, $query);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
				if ($row["requesterID"] == $id) { $type = "sent"; }
				else { $type = "recieved"; }
				$temp = array("requesterID"=>$row["requesterID"], "requesteeID"=>$row["requesteeID"], "requesteePartyTypeID"=>$row["requesteePartyTypeID"], "type"=>$type);
				array_push($requests, $temp);
			}
		}
		return $requests;
	}

	function getGroupInfo($connection, $idList) { // For [See posts, View members
        $groups = array();
		$names = "";
		foreach($idList as $id) { $names .= "'". $id ."',"; }
		if (count($idList) == 0) $names = "NULLL";

		$query = "Select * From groups Where groupID IN (". substr($names, 0, -1) .")";
        $result = mysqli_query($connection, $query);
		
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
				$temp = array("id"=>$row["groupID"], "name"=>$row["name"], "description"=>$row["description"], "srcImg"=>$row["srcImg"], "managerID"=>$row["managerID"]);
				array_push($groups, $temp);
			}
		}
		return $groups;
	}

	function getUserInfo($connection, $idList) { // For [See posts, View members
		$friends = array();
		$names = "";
		if (count($idList) != 0) {
			foreach($idList as $id) { $names .= "'". $id ."',"; }
		}
		if (count($idList) == 0) $names = "NULLL";
		$query = "Select * From users Where userID IN (". substr($names, 0, -1) .")";
		
        $result = mysqli_query($connection, $query);
		
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
				$temp = array("id"=>$row["userID"], "fName"=>$row["fName"], "lName"=>$row["lName"], "bDate"=>$row["bDate"], "collegeID"=>$row["collegeID"], "country"=>$row["country"], "email"=>$row["email"], "srcImg"=>$row["srcImg"]);
				array_push($friends, $temp);
			}
		}
		return $friends;
	}
	
	function getConnections($connection, $id) {
        $connections = array();
		
		$query = "Select * From connections Where userID = '$id'";
        $result = mysqli_query($connection, $query);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
				$temp = array("partyID"=>$row["otherID"], "partyTypeID"=>$row["otherPartyTypeID"]);
				array_push($connections, $temp);
			}
		}
		return $connections;
		
	}

	function getMessages($connection, $userID) {
		$messages = array();
		$sendIDs = array();
		$content = array();
		$query = "Select * From message Where recieveID = '$userID'";
        $result = mysqli_query($connection, $query);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
				$temp1 = array("message"=>$row["message"], "tStamp"=>$row["tStamp"], "sendID"=>$row["sendID"]);
				array_push($content, $temp1);
				array_push($sendIDs, $row["sendID"]);				
			}
			$temp = array("messages"=>$content, "sendIDs"=>$sendIDs);
			array_push($messages, $temp);
		} else $messages = NULL;
		return $messages;
	}
	
	function getCollege($connection, $id) {
		$query = "SELECT * FROM college Where collegeID = '$id'";
		$qResult = mysqli_query($connection, $query);
		$row = mysqli_fetch_assoc($qResult);
		return $row["name"];
	}	

	function getGroup($connection, $id) {
		$query = "SELECT * FROM groups Where groupID = '$id'";
		$qResult = mysqli_query($connection, $query);
		$row = mysqli_fetch_assoc($qResult);
		return $row;
	}	
	
	function getProfilePic($db) {
	    $query = "Select * From users Where userID = " . $_SESSION["userID"];
	    $result = mysqli_query($db, $query);

	    if(mysqli_num_rows($result) > 0) {
	        $row = mysqli_fetch_assoc($result);
			if ($row["srcImg"] == NULL) { $img = "basic.png"; }
			else { $img = $row["srcImg"]; }
			$img = "/CSE-201-Project-Folder/resources/img/". $img;
			return $img;
	    }
	}
	
	// General Functions
	function getIndexRowInfo($array, $id, $keyName) {
		foreach($array as $item) {
			if ($item[$keyName] == $id) return $item;
		}
		return NULL;
	}
	
	// Temp Functions (Move Later)
	function displayGroupPosts($connection, $db) {
		$text = '</div><div class="col-xs-6">';
		$groupPosts = $this->getGroupPosts($connection);
		if ($groupPosts != NULL) {
			$groupInfo = $db->getGroupInfo($connection, $groupPosts["IDs"]);
			foreach($groupPosts["IDs"] as $id) {
				$date = date("M jS, Y", strtotime($groupPosts["tStamp"]));
				$text .= '<h4 class="postHead"><a href="group.php?id='. $id .'">'. $groupInfo["name"] .'</a> posted on '. $date .':</h4>';
				$text .= '<div class="postBody"><p>'. $groupPosts["posts"] .'</p></div>';
			}			
		} else { $text .= '<h1> No group posts found </h2>'; }
		return $text;
	}	
	
	// function getGroupPosts($connection) {
		// $id = $_SESSION['userID'];
        // $query = "SELECT * FROM post WHERE (partyID = '$id' and postPartyTypeID = 2)";
		// echo $query;
        // $result = mysqli_query($connection, $query);
		// $count = 0;
        // if (mysqli_num_rows($result) > 0) {
			// $posts = array();
			// $data = array();
            // while ($row = mysqli_fetch_assoc($result)) {
				// $temp = array("name"=>$result["name"], "description"=>$result["description"], "srcImg"=>$result["srcImg"], "managerID"=>$result["managerID"]);
				// $temp2 = array("Posts"=>$temp, "IDs"=>$result["postID"]);
				// array_merge($data[$count], $temp2);
				// $count++;
				// array_push($posts, "postID"=>$result["postID"], "post"=>$result["post"], "tStamp"=>$result["tStamp"], "partyID"=>$result["partyID"], "partyTypeID"=>$result["partyTypeID"], "postPartyID"=>$result["postPartyID"], "postPartyTypeID"=>$result["postPartyTypeID"]);
			// }
		// } else { $data = NULL; }
		// print_r($data);
		// return $data;
	// }
	
	function displayFriendPosts($connection, $db) {
		$text = '</div><div class="col-xs-6">';
		$friendPosts = $this->getFriendPosts($connection);
		if ($friendPosts != NULL) {
			$friendInfo = $db->getFriendInfo($connection, $friendPosts["IDs"]);
			foreach($groupPosts["IDs"] as $id) {
				$name = $friendInfo["fName"] ." ". $friendInfo["lName"];
				$date = date("M jS, Y", strtotime($friendPosts["tStamp"]));
				$text .= '<h4 class="postHead"><a href="profile.php?id='. $id .'">'. $friendInfo["name"] .'</a> posted on '. $date .':</h4>';
				$text .= '<div class="postBody"><p>'. $friendPosts["posts"] .'</p></div>';
			}			
		} else { $text .= '<h1> No friend posts found </h2>'; }
		return $text;
	}	
	
	// function getFriendPosts($connection) {
		// $id = $_SESSION['userID'];
        // $query = "SELECT * FROM post WHERE (partyID = '$id' and postPartyTypeID = 1)";
        // $result = mysqli_query($connection, $query);
        // if (mysqli_num_rows($result) > 0) {
			// $posts = array();
			// $data = array();
            // while ($row = mysqli_fetch_assoc($result)) {
				// array_push($posts, "postID"=>$result["postID"], "post"=>$result["post"], "tStamp"=>$result["tStamp"], "partyID"=>$result["partyID"], "partyTypeID"=>$result["partyTypeID"], "postPartyID"=>$result["postPartyID"], "postPartyTypeID"=>$result["postPartyTypeID"]);
				// array_push($data, "Posts"=>$posts, "IDs"=>$result["postID"]);
			// }
		// } else { $data = NULL; }
		// return $data;
	// }
	
	
	//  Old Functions
	
	// function getUserGroupPosts($connection, $idList) {
		// $names = "";
		// foreach($idList as $id) { $names .= "'". $id["partyID"] ."',";	}
		
        // $query = "SELECT * FROM post WHERE partyID IN (". substr($names, 0, -1) .") and partyTypeID = 2";
		// echo $query;
        // $result = mysqli_query($connection, $query);

        // if (mysqli_num_rows($result) > 0) {
			// $posts = array();
            // while ($row = mysqli_fetch_assoc($result)) {
				// $temp = array("post"=>$row["post"], "tStamp"=>$row["tStamp"], "srcImg"=>$row["srcImg"], "managerID"=>$row["managerID"]);
				// $temp2 = array("Posts"=>$temp, "groupIDs"=>$result["partyID"], "userIDs"=>$result["postPartyID"]);
				// array_push($posts, $temp2);
			// }
		// } else { $data = NULL; }
		// print_r($posts);
		// return $posts;
	// }	
}

?>