import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
import loginDf2c2a from './login'

export const login = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: login.url(options),
    method: 'get',
})

login.definition = {
    methods: ["get","head"],
    url: '/two-factor-challenge',
} satisfies RouteDefinition<["get","head"]>

login.url = (options?: RouteQueryOptions) => {
    return login.definition.url + queryParams(options)
}

login.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: login.url(options),
    method: 'get',
})

login.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: login.url(options),
    method: 'head',
})

export const enable = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: enable.url(options),
    method: 'post',
})

enable.definition = {
    methods: ["post"],
    url: '/user/two-factor-authentication',
} satisfies RouteDefinition<["post"]>

enable.url = (options?: RouteQueryOptions) => {
    return enable.definition.url + queryParams(options)
}

enable.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: enable.url(options),
    method: 'post',
})

export const confirm = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

confirm.definition = {
    methods: ["post"],
    url: '/user/confirmed-two-factor-authentication',
} satisfies RouteDefinition<["post"]>

confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

export const disable = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: disable.url(options),
    method: 'delete',
})

disable.definition = {
    methods: ["delete"],
    url: '/user/two-factor-authentication',
} satisfies RouteDefinition<["delete"]>

disable.url = (options?: RouteQueryOptions) => {
    return disable.definition.url + queryParams(options)
}

disable.delete = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: disable.url(options),
    method: 'delete',
})

export const qrCode = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: qrCode.url(options),
    method: 'get',
})

qrCode.definition = {
    methods: ["get","head"],
    url: '/user/two-factor-qr-code',
} satisfies RouteDefinition<["get","head"]>

qrCode.url = (options?: RouteQueryOptions) => {
    return qrCode.definition.url + queryParams(options)
}

qrCode.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: qrCode.url(options),
    method: 'get',
})

qrCode.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: qrCode.url(options),
    method: 'head',
})

export const secretKey = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: secretKey.url(options),
    method: 'get',
})

secretKey.definition = {
    methods: ["get","head"],
    url: '/user/two-factor-secret-key',
} satisfies RouteDefinition<["get","head"]>

secretKey.url = (options?: RouteQueryOptions) => {
    return secretKey.definition.url + queryParams(options)
}

secretKey.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: secretKey.url(options),
    method: 'get',
})

secretKey.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: secretKey.url(options),
    method: 'head',
})

export const recoveryCodes = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: recoveryCodes.url(options),
    method: 'get',
})

recoveryCodes.definition = {
    methods: ["get","head"],
    url: '/user/two-factor-recovery-codes',
} satisfies RouteDefinition<["get","head"]>

recoveryCodes.url = (options?: RouteQueryOptions) => {
    return recoveryCodes.definition.url + queryParams(options)
}

recoveryCodes.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: recoveryCodes.url(options),
    method: 'get',
})

recoveryCodes.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: recoveryCodes.url(options),
    method: 'head',
})

export const regenerateRecoveryCodes = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: regenerateRecoveryCodes.url(options),
    method: 'post',
})

regenerateRecoveryCodes.definition = {
    methods: ["post"],
    url: '/user/two-factor-recovery-codes',
} satisfies RouteDefinition<["post"]>

regenerateRecoveryCodes.url = (options?: RouteQueryOptions) => {
    return regenerateRecoveryCodes.definition.url + queryParams(options)
}

regenerateRecoveryCodes.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: regenerateRecoveryCodes.url(options),
    method: 'post',
})

export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/settings/two-factor',
} satisfies RouteDefinition<["get","head"]>

show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

const twoFactor = {
    login: Object.assign(login, loginDf2c2a),
    enable: Object.assign(enable, enable),
    confirm: Object.assign(confirm, confirm),
    disable: Object.assign(disable, disable),
    qrCode: Object.assign(qrCode, qrCode),
    secretKey: Object.assign(secretKey, secretKey),
    recoveryCodes: Object.assign(recoveryCodes, recoveryCodes),
    regenerateRecoveryCodes: Object.assign(regenerateRecoveryCodes, regenerateRecoveryCodes),
    show: Object.assign(show, show),
}

export default twoFactor