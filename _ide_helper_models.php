<?php

// @formatter:off
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * App\Models\AddressHistory
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $address
 * @property string|null $city
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AddressHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AddressHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AddressHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder|AddressHistory whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AddressHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AddressHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AddressHistory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AddressHistory whereUserId($value)
 * @mixin \Eloquent
 * @property string|null $lat
 * @property string|null $lon
 * @method static \Illuminate\Database\Eloquent\Builder|AddressHistory whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AddressHistory whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AddressHistory whereLon($value)
 */
	class AddressHistory extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Admin
 *
 * @property int $phone
 * @property string|null $remember_token
 * @property string $password
 * @method static \Illuminate\Database\Eloquent\Builder|Admin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Admin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Admin query()
 * @method static \Illuminate\Database\Eloquent\Builder|Admin wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereRememberToken($value)
 * @mixin \Eloquent
 */
	class Admin extends \Eloquent implements \Illuminate\Contracts\Auth\Authenticatable {}
}

namespace App\Models{
/**
 * App\Models\Config
 *
 * @property int $name
 * @property string $value
 * @method static \Illuminate\Database\Eloquent\Builder|Config newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Config newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Config query()
 * @method static \Illuminate\Database\Eloquent\Builder|Config whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Config whereValue($value)
 * @mixin \Eloquent
 */
	class Config extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\ErrorReport
 *
 * @property int $id
 * @property int $user_id
 * @property string $error_message
 * @property string $stack_trace
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|ErrorReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ErrorReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ErrorReport query()
 * @method static \Illuminate\Database\Eloquent\Builder|ErrorReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ErrorReport whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ErrorReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ErrorReport whereStackTrace($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ErrorReport whereUserId($value)
 * @mixin \Eloquent
 */
	class ErrorReport extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\FavoriteAddress
 *
 * @property int $id
 * @property int $user_id
 * @property string $address
 * @property string $name
 * @property string $lat
 * @property string $lon
 * @property string|null $city
 * @property string|null $created_at
 * @property string|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress query()
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress whereLon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress whereUserId($value)
 * @mixin \Eloquent
 */
	class FavoriteAddress extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Log
 *
 * @property int $id
 * @property int|null $user_id
 * @property int $isBot
 * @property string|null $message
 * @property string|null $message_value
 * @property string|null $created_at
 * @property string|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Log newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Log newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Log query()
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereIsBot($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereMessageValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereUserId($value)
 * @mixin \Eloquent
 */
	class Log extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Log
 *
 * @property int $id
 * @property string|null $url
 * @property string|null $params
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $result
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|LogApi newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LogApi newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LogApi query()
 * @method static \Illuminate\Database\Eloquent\Builder|LogApi whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogApi whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogApi whereParams($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogApi whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogApi whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogApi whereUrl($value)
 */
	class LogApi extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\OrderHistory
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $address
 * @property int|null $price
 * @property int|null $changed_price
 * @property string|null $comment
 * @property string|null $wishes
 * @property string|null $state_id_chain
 * @property int|null $relevance
 * @property string|null $state
 * @property string|null $fail_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereChangedPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereFailReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereRelevance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereWishes($value)
 * @mixin \Eloquent
 * @property int $usebonus
 * @property string $platform
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory wherePlatform($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereStateIdChain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereUsebonus($value)
 */
	class OrderHistory extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\User
 *
 * @property int $id
 * @property string|null $username
 * @property string|null $firstname
 * @property string|null $lastname
 * @property string|null $userinfo
 * @property string|null $city
 * @property string|null $phone
 * @property string|null $server_id
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User query()
 * @method static Builder|User whereCity($value)
 * @method static Builder|User whereFirstname($value)
 * @method static Builder|User whereId($value)
 * @method static Builder|User whereLastname($value)
 * @method static Builder|User wherePhone($value)
 * @method static Builder|User whereUserinfo($value)
 * @method static Builder|User whereUsername($value)
 * @mixin \Eloquent
 * @property int $isBlocked
 * @property int|null $telegram_id
 * @property int|null $vk_id
 * @property int $need_call
 * @property int $should_reset
 * @property int $lang_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AddressHistory[] $addresses
 * @property-read int|null $addresses_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FavoriteAddress[] $favoriteAddresses
 * @property-read int|null $favorite_addresses_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OrderHistory[] $orders
 * @property-read int|null $orders_count
 * @method static Builder|User whereIsBlocked($value)
 * @method static Builder|User whereLangId($value)
 * @method static Builder|User whereNeedCall($value)
 * @method static Builder|User whereServerId($value)
 * @method static Builder|User whereShouldReset($value)
 * @method static Builder|User whereTelegramId($value)
 * @method static Builder|User whereVkId($value)
 */
	class User extends \Eloquent {}
}

